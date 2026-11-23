<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendVoterInvitation;
use App\Models\Event;
use App\Models\Voter;
use App\Support\Audit;
use App\Support\Plans;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VoterController extends Controller
{
    /** Unambiguous characters (no 0/O, 1/I/L) so printed codes are easy to type. */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const CODE_LENGTH = 8;
    private const MAX_BATCH = 10000;

    /** Member IDs (matric numbers...) match case-insensitively, ignoring spaces. */
    public static function normalizeMemberId(?string $id): ?string
    {
        $id = strtoupper(preg_replace('/\s+/', '', (string) $id) ?? '');
        return $id === '' ? null : $id;
    }

    /** Codes are case-insensitive for voters: store and compare them upper-cased and trimmed. */
    public static function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    /** Paid public voting has no voter list or codes. */
    private function ensureCodeVoting(Event $event): void
    {
        if ($event->isPaid()) {
            throw ValidationException::withMessages(['voters' => ['This event uses paid public voting, so it doesn’t need voter codes.']]);
        }
    }

    public function index(Event $event)
    {
        Gate::authorize('voters', $event);
        return response()->json(Voter::where('event_id', $event->id)->orderBy('id')->get());
    }

    /** Add one voter. The code is generated when not supplied. */
    public function store(Request $request, Event $event)
    {
        Gate::authorize('voters', $event);
        $this->ensureCodeVoting($event);
        $this->normalizeInput($request);
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32', $this->phoneRule()],
            'member_id' => ['nullable', 'string', 'max:64', Rule::unique('voters', 'member_id')->where('event_id', $event->id)],
            'groups' => ['nullable', 'array', 'max:10'],
            'code' => ['nullable', 'string', 'min:4', 'max:64', Rule::unique('voters', 'code')->where('event_id', $event->id), ...($event->ussd_enabled ? ['regex:/^\d+$/'] : [])],
        ], ['member_id.unique' => 'Another voter already has this ID.', 'code.regex' => 'This event uses USSD voting, so codes must be numbers only.']);
        Plans::ensureVoterCapacity($event, 1);

        $voter = Voter::create([
            'event_id' => $event->id,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'member_id' => $data['member_id'] ?? null,
            'groups' => $data['groups'] ?? null,
            'code' => $data['code'] ?? $this->uniqueCodes($event, 1)[0],
        ]);
        Audit::log($event, 'voters.added', 'Voter added: ' . ($voter->name ?: 'anonymous code'));

        return response()->json($voter, 201);
    }

    public function update(Request $request, Event $event, Voter $voter)
    {
        Gate::authorize('voters', $event);
        // Secret ballot: a cast vote can't be identified, so it can't be undone,
        // and organisers can't mark codes as used either. (Checked explicitly:
        // Laravel's "prohibited" rule lets an empty/null value through.)
        if ($request->exists('used_at')) {
            throw ValidationException::withMessages(['used_at' => ['Votes are secret, so a code that has voted can’t be reset.']]);
        }
        $this->normalizeInput($request);
        $data = $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'string', 'email:rfc', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32', $this->phoneRule()],
            'member_id' => ['sometimes', 'nullable', 'string', 'max:64', Rule::unique('voters', 'member_id')->where('event_id', $event->id)->ignore($voter->id)],
            'groups' => ['sometimes', 'nullable', 'array', 'max:10'],
            'code' => ['sometimes', 'string', 'min:4', 'max:64', Rule::unique('voters', 'code')->where('event_id', $event->id)->ignore($voter->id), ...($event->ussd_enabled ? ['regex:/^\d+$/'] : [])],
        ], ['member_id.unique' => 'Another voter already has this ID.', 'code.regex' => 'This event uses USSD voting, so codes must be numbers only.']);

        foreach (['name', 'email', 'phone', 'member_id', 'groups', 'code'] as $field) {
            if (array_key_exists($field, $data)) {
                $voter->{$field} = $data[$field];
            }
        }
        $codeChanged = $voter->isDirty('code');
        $voter->save();
        if ($codeChanged) {
            Audit::log($event, 'voters.code_changed', 'Voting code replaced for ' . ($voter->name ?: 'an anonymous voter'));
        }

        return response()->json($voter);
    }

    /** Deleting a voter who already voted leaves their (anonymous) vote counted. */
    public function destroy(Event $event, Voter $voter)
    {
        Gate::authorize('voters', $event);
        $voter->delete();
        Audit::log($event, 'voters.deleted', 'Voter removed: ' . ($voter->name ?: 'anonymous code') . ($voter->used_at ? ' (had already voted; vote stays counted)' : ''));

        return response()->json([], 204);
    }

    /**
     * USSD: give every voter who hasn't voted a numbers-only code (keypads can't
     * type letters). Old codes stop working, so codes must be sent again.
     */
    public function numericCodes(Event $event)
    {
        Gate::authorize('voters', $event);
        $this->ensureCodeVoting($event);
        abort_unless($event->ussd_enabled, 422, 'Switch on USSD voting first.');

        $voters = Voter::where('event_id', $event->id)->whereNull('used_at')->get(['id', 'code'])
            ->filter(fn ($v) => ! ctype_digit($v->code))->values();
        $codes = $this->uniqueCodes($event, $voters->count());
        DB::transaction(function () use ($voters, $codes) {
            foreach ($voters as $i => $v) {
                Voter::whereKey($v->id)->update(['code' => $codes[$i], 'invited_at' => null, 'invited_via' => null]);
            }
        });
        if ($voters->count()) {
            Audit::log($event, 'voters.numeric_codes', "{$voters->count()} code(s) replaced with numbers-only codes for USSD");
        }

        return response()->json(['replaced' => $voters->count()]);
    }

    /** Privacy: wipe every voter's email and phone for this event (codes and turnout are kept). */
    public function eraseContacts(Event $event)
    {
        Gate::authorize('manage', $event);
        $count = Voter::where('event_id', $event->id)
            ->where(fn ($q) => $q->whereNotNull('email')->orWhereNotNull('phone'))
            ->update(['email' => null, 'phone' => null, 'invite_error' => null]);
        Audit::log($event, 'voters.contacts_erased', "Contact details erased for {$count} voter" . ($count === 1 ? '' : 's'));

        return response()->json(['erased' => $count]);
    }

    /** Create N anonymous codes. */
    public function generate(Request $request, Event $event)
    {
        Gate::authorize('voters', $event);
        $this->ensureCodeVoting($event);
        $data = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:' . self::MAX_BATCH],
        ]);
        Plans::ensureVoterCapacity($event, (int) $data['count']);

        $created = $this->createMany($event, array_fill(0, $data['count'], []));
        Audit::log($event, 'voters.generated', "{$data['count']} anonymous code" . ($data['count'] === 1 ? '' : 's') . ' generated');

        return response()->json(['voters' => $created], 201);
    }

    /**
     * Create one code per person. Accepts either
     *   { voters: [ { name?, email?, phone? }, … ] }  (preferred)
     *   { names: [ "Ada", "Bola" ] }                    (legacy)
     */
    public function import(Request $request, Event $event)
    {
        Gate::authorize('voters', $event);
        $this->ensureCodeVoting($event);

        if (! $request->has('voters') && $request->has('names')) {
            $request->merge(['voters' => array_map(fn ($n) => ['name' => $n], (array) $request->input('names'))]);
        }

        $request->validate([
            'voters' => ['required', 'array', 'min:1', 'max:' . self::MAX_BATCH],
            'voters.*' => ['array'],
            'voters.*.name' => ['nullable', 'string', 'max:255'],
            'voters.*.email' => ['nullable', 'string', 'max:255'],
            'voters.*.phone' => ['nullable', 'string', 'max:32'],
            'voters.*.member_id' => ['nullable', 'string', 'max:64'],
            'voters.*.groups' => ['nullable'],
        ]);

        // Validate contacts row by row so the organiser gets the exact line to fix.
        $rows = [];
        $errors = [];
        // IDs must be unique within the event (already on the list, or twice in this file).
        $takenIds = array_flip(Voter::where('event_id', $event->id)->whereNotNull('member_id')->pluck('member_id')->all());
        foreach (array_values($request->input('voters')) as $i => $row) {
            $memberId = self::normalizeMemberId($row['member_id'] ?? null);
            if ($memberId !== null) {
                if (isset($takenIds[$memberId])) {
                    $errors["voters.{$i}.member_id"] = ['Row ' . ($i + 1) . ': ID "' . $memberId . '" is already on the voter list.'];
                }
                $takenIds[$memberId] = true;
            }
            $email = isset($row['email']) ? strtolower(trim((string) $row['email'])) : '';
            $rawPhone = trim((string) ($row['phone'] ?? ''));
            $phone = PhoneNumber::normalize($rawPhone);
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors["voters.{$i}.email"] = ['Row ' . ($i + 1) . ': "' . $row['email'] . '" is not a valid email address.'];
            }
            if ($rawPhone !== '' && ! PhoneNumber::isValid($phone)) {
                $errors["voters.{$i}.phone"] = ['Row ' . ($i + 1) . ': "' . $row['phone'] . '" ' . PhoneNumber::problem($phone)];
            }
            $rows[] = ['name' => $row['name'] ?? null, 'email' => $email ?: null, 'phone' => $phone, 'member_id' => $memberId, 'groups' => Voter::normalizeGroups($row['groups'] ?? null)];
        }
        if ($errors) {
            throw ValidationException::withMessages(array_slice($errors, 0, 20, true));
        }
        Plans::ensureVoterCapacity($event, count($rows));

        $created = $this->createMany($event, $rows);
        $n = count($created);
        Audit::log($event, 'voters.imported', "{$n} voter" . ($n === 1 ? '' : 's') . ' imported');

        return response()->json(['voters' => $created], 201);
    }

    /**
     * Send voting codes by email and/or SMS (queued; delivered by the queue worker).
     * Voters who have already voted are never messaged.
     */
    public function invite(Request $request, Event $event)
    {
        Gate::authorize('voters', $event);
        $data = $request->validate([
            'channel' => ['required', Rule::in(['email', 'sms', 'both'])],
            'audience' => ['required', Rule::in(['not_invited', 'pending', 'selected'])],
            'voter_ids' => ['required_if:audience,selected', 'array', 'max:' . self::MAX_BATCH],
            'voter_ids.*' => ['integer'],
        ]);

        Plans::ensureCanMessage($event);
        if ($event->status === Event::STATUS_COMPLETED) {
            throw ValidationException::withMessages(['channel' => ['Voting has closed for this event, so invitations can no longer be sent.']]);
        }

        $query = Voter::where('event_id', $event->id)->whereNull('used_at');
        if ($data['audience'] === 'not_invited') {
            $query->whereNull('invited_at');
        } elseif ($data['audience'] === 'selected') {
            $query->whereIn('id', $data['voter_ids'] ?? []);
        }

        return response()->json(self::sendMessages($event, $query, $data['channel'], 'invite'));
    }

    /** Remind everyone who hasn't voted yet (Pro). */
    public function remind(Request $request, Event $event)
    {
        Gate::authorize('voters', $event);
        $data = $request->validate(['channel' => ['required', Rule::in(['email', 'sms', 'both'])]]);
        Plans::ensureCanMessage($event);
        if (! $event->isOpen()) {
            throw ValidationException::withMessages(['channel' => ['Reminders can only be sent while voting is open.']]);
        }

        return response()->json(self::sendMessages($event, Voter::where('event_id', $event->id)->whereNull('used_at'), $data['channel'], 'reminder'));
    }

    /**
     * The organiser shared this voter's code themselves (e.g. on WhatsApp from
     * their phone). Recorded so the voter no longer shows as "not sent".
     */
    public function markShared(Request $request, Event $event, Voter $voter)
    {
        Gate::authorize('voters', $event);
        $data = $request->validate(['channel' => ['required', Rule::in(['whatsapp'])]]);
        $voter->forceFill(['invited_at' => now(), 'invited_via' => $data['channel'], 'invite_error' => null])->save();

        return response()->json($voter);
    }

    /**
     * Queue one message per reachable voter in $query. Shared by invitations,
     * reminders and the scheduled reminder command.
     *
     * @return array{queued: int, email: int, sms: int, failed: int, queue: string}
     */
    public static function sendMessages(Event $event, $query, string $channel, string $kind): array
    {
        $channels = $channel === 'both' ? ['email', 'sms'] : [$channel];

        // Only voters reachable on at least one requested channel.
        $query->where(function ($q) use ($channels) {
            if (in_array('email', $channels, true)) $q->orWhereNotNull('email');
            if (in_array('sms', $channels, true)) $q->orWhereNotNull('phone');
        });

        // Without a queue worker (QUEUE_CONNECTION=sync) messages are sent right
        // after the HTTP response, so the organiser never waits on the mail server.
        $noWorker = config('queue.default') === 'sync';
        if ($noWorker) {
            @set_time_limit(0);       // large lists keep sending after the response
            ignore_user_abort(true);  // …even if the browser tab is closed
        }

        $counts = ['queued' => 0, 'email' => 0, 'sms' => 0, 'failed' => 0];
        $query->select(['id', 'email', 'phone'])->chunkById(500, function ($voters) use ($channels, $kind, &$counts, $noWorker) {
            foreach ($voters as $voter) {
                try {
                    // Delivery errors are handled inside the job; this only guards
                    // against the queue itself being unavailable.
                    $noWorker
                        ? SendVoterInvitation::dispatchAfterResponse($voter->id, $channels, $kind)
                        : SendVoterInvitation::dispatch($voter->id, $channels, $kind);
                } catch (\Throwable $e) {
                    report($e);
                    $counts['failed']++;
                    Voter::whereKey($voter->id)->update(['invite_error' => 'Could not be queued: ' . Str::limit($e->getMessage(), 200)]);
                    continue;
                }
                $counts['queued']++;
                if (in_array('email', $channels, true) && $voter->email) $counts['email']++;
                if (in_array('sms', $channels, true) && $voter->phone) $counts['sms']++;
            }
        });

        if ($counts['queued'] > 0) {
            $via = implode(' and ', array_map(fn ($c) => $c === 'sms' ? 'SMS' : 'email', $channels));
            $n = $counts['queued'] . ' voter' . ($counts['queued'] === 1 ? '' : 's');
            Audit::log($event, $kind === 'reminder' ? 'voters.reminded' : 'voters.invited',
                $kind === 'reminder' ? "Reminder sent to {$n} who hadn’t voted, by {$via}" : "Voting codes sent to {$n} by {$via}");
        }

        return $counts + ['queue' => config('queue.default')];
    }

    public function validateCode(Request $request, string $token)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);
        $event = Event::publicToken($token)->firstOrFail();
        if ($event->isPaid() || ! $event->isOpen()) {
            return response()->json(['valid' => false, 'message' => 'This election is not open.'], 403);
        }
        $voter = Voter::where('event_id', $event->id)
            ->whereNull('used_at')
            ->where('code', self::normalizeCode($data['code']))
            ->first(['id', 'name', 'groups']);

        // The voter's own name (from the organiser's import) lets them confirm the
        // code is theirs. Only returned for a valid, unused code (rate-limited).
        // `categories`: the ones this voter may vote in (voter groups).
        return $voter
            ? response()->json(['valid' => true, 'name' => $voter->name, 'categories' => $event->categories()->get(['id', 'groups'])
                ->filter(fn ($c) => $c->allows($voter->groups))->pluck('id')->values()])
            : response()->json(['valid' => false, 'message' => 'Invalid or used voting code.'], 422);
    }

    /** The voter groups in use on this event, with how many voters are in each. */
    public function groups(Event $event)
    {
        Gate::authorize('view', $event);
        $counts = [];
        foreach (Voter::where('event_id', $event->id)->whereNotNull('groups')->pluck('groups') as $groups) {
            foreach ((array) $groups as $g) {
                $key = mb_strtolower($g);
                $counts[$key] ??= ['name' => $g, 'voters' => 0];
                $counts[$key]['voters']++;
            }
        }
        // Groups a category is limited to, even if no voter has them yet.
        foreach ($event->categories()->whereNotNull('groups')->pluck('groups') as $groups) {
            foreach ((array) $groups as $g) {
                $counts[mb_strtolower($g)] ??= ['name' => $g, 'voters' => 0];
            }
        }
        return response()->json(collect($counts)->sortBy(fn ($g) => mb_strtolower($g['name']))->values());
    }

    // ------------------------------------------------------------------ helpers

    /** Trim/normalise code, email and phone before validation. */
    private function normalizeInput(Request $request): void
    {
        $merge = [];
        if ($request->has('member_id')) $merge['member_id'] = self::normalizeMemberId($request->input('member_id'));
        if ($request->has('groups')) $merge['groups'] = Voter::normalizeGroups($request->input('groups'));
        if ($request->filled('code')) $merge['code'] = self::normalizeCode((string) $request->input('code'));
        elseif ($request->has('code')) $merge['code'] = null;
        if ($request->has('email')) $merge['email'] = $request->filled('email') ? strtolower(trim((string) $request->input('email'))) : null;
        if ($request->has('phone')) {
            $raw = $request->input('phone');
            // Keep unparseable input (e.g. "abc") so validation rejects it instead of dropping it.
            $merge['phone'] = PhoneNumber::normalize($raw) ?? ($request->filled('phone') ? (string) $raw : null);
        }
        $request->merge($merge);
    }

    private function phoneRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if ($value !== null && ! PhoneNumber::isValid($value)) {
                $fail('This phone number ' . PhoneNumber::problem($value));
            }
        };
    }

    /**
     * @param  array<int, array{name?: ?string, email?: ?string, phone?: ?string}>  $rows
     * @return array<int, array{name: ?string, email: ?string, phone: ?string, code: string}>
     */
    private function createMany(Event $event, array $rows): array
    {
        return DB::transaction(function () use ($event, $rows) {
            $codes = $this->uniqueCodes($event, count($rows));
            $now = now();
            $out = [];
            $insert = [];
            foreach (array_values($rows) as $i => $row) {
                $name = isset($row['name']) && trim((string) $row['name']) !== '' ? trim((string) $row['name']) : null;
                $groups = $row['groups'] ?? null;
                $voter = ['name' => $name, 'email' => $row['email'] ?? null, 'phone' => $row['phone'] ?? null, 'member_id' => $row['member_id'] ?? null, 'code' => $codes[$i]];
                $out[] = $voter + ['groups' => $groups];
                $insert[] = $voter + ['groups' => $groups ? json_encode($groups) : null, 'event_id' => $event->id, 'created_at' => $now, 'updated_at' => $now];
            }
            foreach (array_chunk($insert, 500) as $chunk) {
                Voter::insert($chunk);
            }
            return $out;
        });
    }

    /** @return string[] codes not yet used in this event */
    /**
     * Codes not yet used in this event. Events with USSD voting get numbers
     * only (10 digits), because phone keypads can't type letters in USSD.
     */
    private function uniqueCodes(Event $event, int $count): array
    {
        $taken = array_flip(Voter::where('event_id', $event->id)->pluck('code')->all());
        $codes = [];
        [$alphabet, $length] = $event->ussd_enabled ? ['0123456789', 10] : [self::CODE_ALPHABET, self::CODE_LENGTH];
        $max = strlen($alphabet) - 1;
        while (count($codes) < $count) {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $alphabet[random_int(0, $max)];
            }
            if (! isset($taken[$code])) {
                $taken[$code] = true;
                $codes[] = $code;
            }
        }
        return $codes;
    }
}
