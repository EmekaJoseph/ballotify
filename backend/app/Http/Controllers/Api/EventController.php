<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BallotReceipt;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\Payout;
use App\Models\User;
use App\Models\VotePurchase;
use App\Policies\EventPolicy;
use App\Support\Audit;
use App\Support\Paystack;
use App\Support\Plans;
use App\Models\Voter;
use App\Support\MeetingRules;
use App\Support\Templates;
use App\Support\Timeline;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as Filesystem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    private const VISIBILITY_LABELS = [
        Event::RESULTS_LIVE => 'live',
        Event::RESULTS_AFTER_CLOSE => 'after voting closes',
        Event::RESULTS_PRIVATE => 'organiser only',
    ];

    private const BRAND_DIR = 'branding';

    /** Counts shown on the dashboard for each event. */
    private function counts(): array
    {
        return [
            'categories',
            'candidates',
            'voters',
            'voters as voted_count' => fn ($q) => $q->whereNotNull('used_at'),
            'members',
        ];
    }

    /** Event as the dashboard sees it: plan limits plus this user's role and abilities. */
    private function present(Event $event, User $user): Event
    {
        Plans::withLimits($event);
        $event->setAttribute('my_role', EventPolicy::roleOf($user, $event));
        $event->setAttribute('abilities', EventPolicy::abilitiesOf($user, $event));
        // Name only: never serialise the owner's user record (it holds payout details).
        $event->setAttribute('owner_name', $event->relationLoaded('user') ? $event->user?->name : $event->user()->value('name'));
        // The shortcode voters dial (set by the platform in .env); null = USSD not available here.
        $event->setAttribute('ussd_service_code', config('services.ussd.service_code') ?: null);
        if ($event->isPaid()) {
            $event->setAttribute('votes_sold', (int) $event->purchases()->where('status', VotePurchase::SUCCESS)->sum('quantity'));
        }
        return $event;
    }

    /**
     * The closing time must not be before the opening time. Either bound may be
     * empty (open immediately / no fixed end), which Laravel's after_or_equal rule
     * does not handle, so the check is done here.
     */
    private function ensureValidWindow(mixed $start, mixed $end): void
    {
        if ($start && $end && Carbon::parse($end)->lt(Carbon::parse($start))) {
            throw ValidationException::withMessages([
                'ends_at' => ['The closing time must be after the opening time.'],
            ]);
        }
    }

    private function priceRules(): array
    {
        return ['nullable', 'integer', 'min:10', 'max:100000'];
    }

    public function templates()
    {
        return response()->json(Templates::catalogue());
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $events = Event::accessibleBy($user)
            ->with('user')
            ->withCount($this->counts())
            ->latest()
            ->get();
        $events->each(fn (Event $e) => $this->present($e, $user)->unsetRelation('user'));

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'results_visibility' => ['nullable', Rule::in(Event::RESULTS_OPTIONS)],
            'voting_mode' => ['nullable', Rule::in(Event::MODES)],
            'vote_price_naira' => $this->priceRules(),
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'template' => ['nullable', Rule::in(array_keys(Templates::all()))],
        ]);
        $this->ensureValidWindow($data['starts_at'] ?? null, $data['ends_at'] ?? null);
        $template = isset($data['template']) ? Templates::all()[$data['template']] : [];

        $mode = $data['voting_mode'] ?? $template['voting_mode'] ?? Event::MODE_CODES;
        $price = $data['vote_price_naira'] ?? $template['vote_price_naira'] ?? null;
        if ($mode === Event::MODE_PAID && ! $price) {
            throw ValidationException::withMessages(['vote_price_naira' => ['Set a price per vote for paid voting.']]);
        }

        $event = DB::transaction(function () use ($request, $data, $template, $mode, $price) {
            $event = Event::create([
                'user_id' => $request->user()->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'link_token' => Str::uuid()->toString(),
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'results_visibility' => $data['results_visibility'] ?? $template['results_visibility'] ?? Event::RESULTS_AFTER_CLOSE,
                'voting_mode' => $mode,
                'vote_price_naira' => $mode === Event::MODE_PAID ? $price : null,
            ]);
            if (isset($data['template'])) {
                Templates::apply($event, $data['template']);
            }
            return $event;
        });
        Audit::log($event, 'event.created', "Event “{$event->name}” created" . (isset($data['template']) ? " from the “{$template['name']}” template" : ''));

        return response()->json($this->present($event->loadCount($this->counts()), $request->user()), 201);
    }

    public function show(Request $request, Event $event)
    {
        Gate::authorize('view', $event);
        // Voters are fetched separately (GET /events/{id}/voters): an event can have thousands.
        $event->load(['categories', 'candidates'])->loadCount($this->counts());
        return response()->json($this->present($event, $request->user()));
    }

    public function update(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'results_visibility' => ['sometimes', Rule::in(Event::RESULTS_OPTIONS)],
            'voting_mode' => ['sometimes', Rule::in(Event::MODES)],
            'vote_price_naira' => array_merge(['sometimes'], $this->priceRules()),
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'reminder_at' => ['sometimes', 'nullable', 'date'],
            'reminder_channel' => ['sometimes', 'nullable', Rule::in(['email', 'sms', 'both'])],
            'self_registration' => ['sometimes', 'boolean'],
            'member_id_label' => ['sometimes', 'nullable', 'string', 'max:40'],
            'language' => ['sometimes', 'string', \Illuminate\Validation\Rule::in(config('ballotify.languages'))],
            'ussd_enabled' => ['sometimes', 'boolean'],
            'nominations_enabled' => ['sometimes', 'boolean'],
            'nominations_ends_at' => ['sometimes', 'nullable', 'date'],
            // Meeting rules (Pro).
            'weighted_votes' => ['sometimes', 'boolean'],
            'proxies_enabled' => ['sometimes', 'boolean'],
            'max_proxies' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'quorum_type' => ['sometimes', 'nullable', Rule::in(Event::QUORUM_TYPES)],
            'quorum_value' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100000000'],
        ]);
        // Compare against the stored value for whichever bound was not sent.
        $start = array_key_exists('starts_at', $data) ? $data['starts_at'] : $event->starts_at;
        $end = array_key_exists('ends_at', $data) ? $data['ends_at'] : $event->ends_at;
        $this->ensureValidWindow($start, $end);

        $before = $event->only(['name', 'voting_mode', 'vote_price_naira', 'results_visibility', 'starts_at', 'ends_at', 'reminder_at', 'self_registration', 'ussd_enabled', 'nominations_enabled',
            'weighted_votes', 'proxies_enabled', 'max_proxies', 'quorum_type', 'quorum_value']) + ['status' => $event->status];

        if (array_key_exists('voting_mode', $data) && $data['voting_mode'] !== $event->voting_mode) {
            if ($event->ballot_locked) {
                throw ValidationException::withMessages(['voting_mode' => ['The voting mode can’t be changed after voting has started.']]);
            }
            if ($data['voting_mode'] === Event::MODE_PAID && $event->voters()->exists()) {
                throw ValidationException::withMessages(['voting_mode' => ['Remove the voter codes first: paid voting doesn’t use codes.']]);
            }
        }
        $mode = $data['voting_mode'] ?? $event->voting_mode;
        $price = array_key_exists('vote_price_naira', $data) ? $data['vote_price_naira'] : $event->vote_price_naira;
        if ($mode === Event::MODE_PAID && ! $price) {
            throw ValidationException::withMessages(['vote_price_naira' => ['Set a price per vote for paid voting.']]);
        }
        if ($mode !== Event::MODE_PAID) {
            $data['vote_price_naira'] = null;
        }

        // Scheduled reminder (Pro). Clearing it is always allowed.
        if (! empty($data['reminder_at'])) {
            Plans::ensureCanMessage($event);
            $at = Carbon::parse($data['reminder_at']);
            if ($at->isPast()) {
                throw ValidationException::withMessages(['reminder_at' => ['Choose a reminder time in the future.']]);
            }
            if ($end && $at->gte(Carbon::parse($end))) {
                throw ValidationException::withMessages(['reminder_at' => ['The reminder must be sent before voting closes.']]);
            }
            $data['reminder_channel'] = $data['reminder_channel'] ?? $event->reminder_channel ?? 'email';
        }
        if (array_key_exists('reminder_at', $data)) {
            $event->reminder_sent_at = null;
            if (! $data['reminder_at']) {
                $data['reminder_channel'] = null;
            }
        }

        // Optional voting features. Self-registration and USSD send messages / use paid channels: Pro.
        if (! empty($data['self_registration']) && ! $event->self_registration) {
            Plans::ensureCanMessage($event);
            if ($event->isPaid()) {
                throw ValidationException::withMessages(['self_registration' => ['Paid voting doesn’t use voter codes.']]);
            }
        }
        if (array_key_exists('ussd_enabled', $data)) {
            if ($data['ussd_enabled'] && ! $event->ussd_enabled) {
                Plans::ensureCanMessage($event);
                if ($event->isPaid()) {
                    throw ValidationException::withMessages(['ussd_enabled' => ['USSD voting works with voter codes, not paid voting.']]);
                }
                $event->ussd_code ??= self::newUssdCode();
            }
            $event->ussd_enabled = (bool) $data['ussd_enabled'];
            unset($data['ussd_enabled']);
        }
        if (! empty($data['nominations_ends_at']) && $start && Carbon::parse($data['nominations_ends_at'])->gt(Carbon::parse($start))) {
            throw ValidationException::withMessages(['nominations_ends_at' => ['Nominations must close before voting opens.']]);
        }

        $data = $this->checkMeetingRules($event, $data);

        $event->fill($data)->save();
        $event = $event->fresh();
        $this->auditChanges($event, $before);

        return response()->json($this->present($event->loadCount($this->counts()), $request->user()));
    }

    /** Pro: the organiser's logo and accent colour on the ballot and results pages. */
    public function branding(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        Plans::ensureCustomBranding($event);
        $data = $request->validate([
            'brand_color' => ['sometimes', 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', File::image()->max('2mb')],
            'remove_logo' => ['sometimes', 'boolean'],
        ], ['brand_color.regex' => 'Use a colour like #0F766E.']);

        if (array_key_exists('brand_color', $data)) {
            $event->brand_color = $data['brand_color'] ? strtoupper($data['brand_color']) : null;
        }
        if ($request->hasFile('logo') || $request->boolean('remove_logo')) {
            $this->deleteLogo($event->brand_logo_path);
            $event->brand_logo_path = $request->hasFile('logo') ? $this->storeLogo($request->file('logo')) : null;
        }
        $event->save();
        Audit::log($event, 'event.branding', 'Ballot branding updated');

        return response()->json($this->present($event->loadCount($this->counts()), $request->user()));
    }

    public function destroy(Event $event)
    {
        Gate::authorize('own', $event);
        $this->deleteLogo($event->brand_logo_path);
        $event->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Copy an event's ballot (settings, categories, pick limits, candidates with
     * descriptions and photos) into a new draft. Voters, codes, totals and the
     * activity log are never copied; dates are cleared so it can be rescheduled.
     */
    public function duplicate(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $event->load(['categories', 'candidates']);

        $copy = DB::transaction(function () use ($request, $event) {
            $copy = Event::create([
                'user_id' => $request->user()->id,
                'name' => Str::limit($event->name, 245, '') . ' (copy)',
                'description' => $event->description,
                'link_token' => Str::uuid()->toString(),
                'results_visibility' => $event->results_visibility,
                'voting_mode' => $event->voting_mode,
                'vote_price_naira' => $event->vote_price_naira,
                'brand_color' => $event->brand_color,
                'weighted_votes' => $event->weighted_votes,
                'proxies_enabled' => $event->proxies_enabled,
                'max_proxies' => $event->max_proxies,
                'quorum_type' => $event->quorum_type,
                'quorum_value' => $event->quorum_value,
                'starts_at' => null,
                'ends_at' => null,
            ]);

            $categoryMap = [];
            foreach ($event->categories as $category) {
                $categoryMap[$category->id] = Category::create([
                    'event_id' => $copy->id,
                    'name' => $category->name,
                    'max_choices' => $category->max_choices,
                    'shuffle_candidates' => $category->shuffle_candidates,
                    'method' => $category->method,
                    'description' => $category->description,
                    'pass_rule' => $category->pass_rule,
                    'groups' => $category->groups,
                ])->id;
            }
            foreach ($event->candidates as $candidate) {
                if (! isset($categoryMap[$candidate->category_id])) {
                    continue;
                }
                Candidate::create([
                    'event_id' => $copy->id,
                    'category_id' => $categoryMap[$candidate->category_id],
                    'name' => $candidate->name,
                    'description' => $candidate->description,
                    'image_path' => CandidateController::copyImage($candidate->image_path),
                    'motion_choice' => $candidate->motion_choice,
                ]);
            }

            return $copy;
        });

        Audit::log($copy, 'event.created', "Event created as a copy of “{$event->name}”");

        return response()->json($this->present($copy->loadCount($this->counts()), $request->user()), 201);
    }

    public function link(Event $event)
    {
        Gate::authorize('view', $event);
        $frontend = rtrim(config('app.frontend_url'), '/');
        return response()->json([
            'token' => $event->link_token,
            'public_url' => "{$frontend}/vote/{$event->link_token}",
            'results_url' => "{$frontend}/event/{$event->link_token}/results",
        ]);
    }

    /** Organiser activity log, newest first. */
    public function activity(Event $event)
    {
        Gate::authorize('view', $event);
        $logs = $event->auditLogs()->with('user:id,name')->latest('created_at')->latest('id')->limit(500)->get();
        return response()->json($logs);
    }

    /** Organisers can always see the tally, whatever the public visibility setting. */
    public function adminResults(Event $event)
    {
        Gate::authorize('view', $event);
        return response()->json($this->buildResults($event));
    }

    /** Paid voting: money raised, the platform fee, payouts and recent purchases. */
    public function earnings(Event $event)
    {
        Gate::authorize('manage', $event);
        $paid = $event->purchases()->where('status', VotePurchase::SUCCESS);
        $gross = (int) (clone $paid)->sum('amount_kobo');
        $fee = (int) (clone $paid)->sum('fee_kobo');
        // Split payments went straight to the organiser's bank: count them as paid out.
        $split = (clone $paid)->whereNotNull('subaccount');
        $autoPaid = (int) (clone $split)->sum('amount_kobo') - (int) (clone $split)->sum('fee_kobo');
        $paidOut = (int) $event->payouts()->sum('amount_kobo') + $autoPaid;

        $recent = $event->purchases()->whereIn('status', [VotePurchase::SUCCESS, VotePurchase::LATE])
            ->latest('paid_at')->limit(50)
            ->get(['candidate_name', 'quantity', 'amount_kobo', 'name', 'email', 'status', 'paid_at'])
            ->map(fn ($p) => [
                'candidate' => $p->candidate_name,
                'quantity' => $p->quantity,
                'amount_naira' => $p->amount_kobo / 100,
                'buyer' => $p->name ?: self::maskEmail($p->email),
                'status' => $p->status,
                'paid_at' => $p->paid_at,
            ]);

        return response()->json([
            'currency' => 'NGN',
            'fee_percent' => Plans::settings()['paid_vote_fee_percent'],
            'votes_sold' => (int) (clone $paid)->sum('quantity'),
            'payments' => (clone $paid)->count(),
            'gross_naira' => $gross / 100,
            'fee_naira' => $fee / 100,
            'net_naira' => ($gross - $fee) / 100,
            'paid_out_naira' => $paidOut / 100,
            'balance_naira' => ($gross - $fee - $paidOut) / 100,
            'auto_paid_naira' => $autoPaid / 100,
            'automatic_payouts' => (bool) $event->user?->paystack_subaccount_code,
            'late_payments' => $event->purchases()->where('status', VotePurchase::LATE)->count(),
            'payouts' => Payout::where('event_id', $event->id)->latest()->get(['amount_kobo', 'note', 'created_at'])
                ->map(fn ($p) => ['amount_naira' => $p->amount_kobo / 100, 'note' => $p->note, 'created_at' => $p->created_at]),
            'recent' => $recent,
        ]);
    }

    public function publicShow(string $token)
    {
        $event = Event::publicToken($token)->firstOrFail();

        // Not open yet / already closed: tell the client why, without exposing the ballot.
        if (! $event->isOpen()) {
            return response()->json([
                'message' => $event->status === Event::STATUS_UPCOMING
                    ? __('Voting has not started yet.')
                    : __('Voting has ended.'),
                'name' => $event->name,
                'language' => $event->language,
                'status' => $event->status,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
                'results_public' => $event->resultsArePublic(),
                'brand' => $this->publicBrand($event),
                'nominations_open' => $event->nominationsOpen(),
            ], 403);
        }

        $event->load([
            'categories:id,event_id,name,max_choices,shuffle_candidates,method,description,pass_rule',
            'candidates:id,event_id,category_id,name,description,image_path,motion_choice',
        ]);

        // Shuffled categories: a fresh random order for every ballot that is opened.
        $shuffled = $event->categories->where('shuffle_candidates', true)->pluck('id')->flip();
        $candidates = $event->candidates->groupBy('category_id')
            ->flatMap(fn ($group, $categoryId) => isset($shuffled[$categoryId]) ? $group->shuffle() : $group)
            ->values();

        $paymentsReady = \App\Support\Payments::available();

        return response()->json([
            'id' => $event->id,
            'name' => $event->name,
            'description' => $event->description,
            'starts_at' => $event->starts_at,
            'ends_at' => $event->ends_at,
            'status' => $event->status,
            'voting_mode' => $event->voting_mode,
            'vote_price_naira' => $event->isPaid() ? $event->vote_price_naira : null,
            'payments_enabled' => $event->isPaid() ? $paymentsReady : null,
            'min_payment_naira' => $event->isPaid() ? (int) config('ballotify.billing.min_vote_payment_naira', 50) : null,
            'results_visibility' => $event->results_visibility,
            // Free events show "Powered by Ballotify" on the ballot.
            'branding' => Plans::limits($event)['branding'],
            'brand' => $this->publicBrand($event),
            // Voters can request their own code with an ID on the organiser's list.
            'self_registration' => $event->self_registration && ! $event->isPaid() && Plans::isPro($event),
            'member_id_label' => $event->member_id_label ?: 'ID',
            'language' => $event->language,
            'categories' => $event->categories->map->only(['id', 'event_id', 'name', 'max_choices', 'method', 'description', 'pass_rule']),
            'candidates' => $candidates,
        ]);
    }

    /** Public results page, subject to the event's results visibility. */
    public function results(string $token)
    {
        $event = Event::publicToken($token)->firstOrFail();

        if (! $event->resultsArePublic()) {
            return response()->json([
                'message' => $event->results_visibility === Event::RESULTS_PRIVATE
                    ? __('The organiser has chosen not to publish these results.')
                    : __('Results will be published when voting closes.'),
                'visibility' => $event->results_visibility,
                'event' => [
                    'name' => $event->name,
                    'language' => $event->language,
                    'status' => $event->status,
                    'starts_at' => $event->starts_at,
                    'ends_at' => $event->ends_at,
                    'brand' => $this->publicBrand($event),
                ],
            ], 403);
        }

        return response()->json($this->buildResults($event));
    }

    /**
     * A candidate's photo through the API (with CORS headers), so campaign
     * cards can draw it onto a canvas and the image can still be downloaded.
     */
    public function candidatePhoto(string $token, int $candidate)
    {
        $event = Event::publicToken($token)->firstOrFail();
        $c = \App\Models\Candidate::where('event_id', $event->id)->findOrFail($candidate);
        $path = $c->image_path ? realpath(public_path($c->image_path)) : false;
        abort_unless($path && str_starts_with($path, realpath(public_path())) && is_file($path), 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=3600']);
    }

    /** Voters check that their ballot was counted, using the receipt shown after voting. */
    public function checkReceipt(Request $request, string $token)
    {
        $data = $request->validate(['receipt' => ['required', 'string', 'max:40']]);
        $event = Event::publicToken($token)->firstOrFail();

        if ($event->isPaid()) {
            $purchase = VotePurchase::where('event_id', $event->id)->where('reference', strtoupper(trim($data['receipt'])))->first();
            if (! $purchase) {
                return response()->json(['valid' => false, 'message' => __('No payment with this reference was found for this event.')], 404);
            }
            return response()->json([
                'valid' => $purchase->status === VotePurchase::SUCCESS,
                'status' => $purchase->status,
                'counted_at' => $purchase->paid_at?->startOfMinute(),
                'votes' => $purchase->quantity,
                'candidate' => $purchase->candidate_name,
                'message' => match ($purchase->status) {
                    VotePurchase::SUCCESS => __($purchase->quantity === 1 ? ':count vote for :candidate counted.' : ':count votes for :candidate counted.', ['count' => $purchase->quantity, 'candidate' => $purchase->candidate_name]),
                    VotePurchase::LATE => __('This payment arrived after voting closed, so it was not counted. The organiser will arrange a refund.'),
                    VotePurchase::PENDING => __('This payment is still being confirmed.'),
                    default => __('This payment did not go through, so no votes were added.'),
                },
            ]);
        }

        $receipt = BallotReceipt::where('event_id', $event->id)
            ->where('code_hash', BallotReceipt::hash($event->id, $data['receipt']))
            ->first();

        return $receipt
            ? response()->json([
                'valid' => true,
                // Minute precision only.
                'counted_at' => $receipt->created_at->startOfMinute(),
                'message' => __('This ballot was received and counted.'),
            ])
            : response()->json(['valid' => false, 'message' => __('No ballot with this receipt was found for this event. Check the code and try again.')], 404);
    }

    // ------------------------------------------------------------------ helpers

    private function publicBrand(Event $event): ?array
    {
        if (! Plans::isPro($event) || (! $event->brand_color && ! $event->brand_logo_path)) {
            return null;
        }
        return ['color' => $event->brand_color, 'logo_url' => $event->brand_logo_url];
    }

    private static function maskEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return 'Anonymous';
        }
        [$user, $domain] = explode('@', $email, 2);
        return mb_substr($user, 0, 2) . str_repeat('•', max(1, min(6, mb_strlen($user) - 2))) . '@' . $domain;
    }

    private function buildResults(Event $event): array
    {
        $categories = $event->categories()
            ->with(['candidates' => fn ($q) => $q->select(['id', 'category_id', 'name', 'description', 'image_path', 'votes', 'motion_choice'])])
            ->orderBy('id')
            ->get();

        // Voter groups: how many voters may vote in each category (for "x of y eligible").
        $voterGroups = $event->isPaid() ? collect() : $event->voters()->pluck('groups')
            ->map(fn ($g) => is_string($g) ? json_decode($g, true) : $g);
        $totalVotes = 0;
        $results = $categories->map(function ($category) use (&$totalVotes, $voterGroups) {
            $tally = \App\Support\Tally::category($category, $category->candidates);
            $position = array_flip($tally['order']);
            $candidates = $category->candidates
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'description' => $c->description,
                    'image_url' => $c->image_url,
                    // Ranked categories: first-choice votes (the rounds show the rest).
                    'votes' => (int) $c->votes,
                    'motion_choice' => $c->motion_choice,
                ])
                ->sortBy(fn ($c) => $position[$c['id']] ?? PHP_INT_MAX)
                ->values();
            $totalVotes += $candidates->sum('votes');
            $leaderId = $tally['winners'][0] ?? null;

            return [
                'id' => $category->id,
                'name' => $category->name,
                'max_choices' => $category->max_choices,
                'method' => $category->method,
                'description' => $category->description,
                'pass_rule' => $category->pass_rule,
                'motion' => $tally['motion'] ?? null,
                // Ballots that answered this category: the base for "% of voters".
                'ballots' => (int) $category->ballots,
                // Limited to some voter groups: how many voters may vote in it.
                'groups' => $category->groups,
                'eligible' => $category->groups ? $voterGroups->filter(fn ($g) => $category->allows($g))->count() : null,
                'winners' => $tally['winners'],
                'rounds' => $tally['rounds'],
                'candidates' => $candidates,
                'leader' => $leaderId ? $candidates->firstWhere('id', $leaderId) : $candidates->first(),
            ];
        });

        $paid = $event->isPaid();
        $buyers = $paid ? $event->purchases()->where('status', VotePurchase::SUCCESS)->distinct()->count('email') : null;

        return [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'language' => $event->language,
                'status' => $event->status,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
                'results_visibility' => $event->results_visibility,
                'voting_mode' => $event->voting_mode,
                'branding' => Plans::limits($event)['branding'],
                'brand' => $this->publicBrand($event),
            ],
            'stats' => [
                'total_votes' => $totalVotes,
                'total_voters' => $paid ? $buyers : $event->voters()->count(),
                'voted' => $paid ? $buyers : $event->voters()->whereNotNull('used_at')->count(),
                'total_candidates' => $event->candidates()->count(),
            ],
            // Transparency: how many organiser changes happened after voting began.
            'integrity' => [
                // Edits to the ballot itself (candidates, categories) once votes existed; not settings, certification etc.
                'changes_during_voting' => $event->auditLogs()->where('during_voting', true)
                    ->where(fn ($q) => $q->where('action', 'like', 'candidate.%')->orWhere('action', 'like', 'category.%'))
                    ->count(),
                // Test-run resets (when, by whom): never hidden.
                'votes_reset' => VoteResetController::history($event),
            ],
            'timeline' => Timeline::for($event),
            // Weighted votes / proxies / quorum (null when none are on).
            'rules' => MeetingRules::summary($event),
            'categories' => $results,
        ];
    }

    private function auditChanges(Event $event, array $before): void
    {
        $fmt = fn ($d) => $d ? Carbon::parse($d)->toDayDateTimeString() : 'none';

        if ($before['status'] === Event::STATUS_ACTIVE && $event->status === Event::STATUS_COMPLETED) {
            Audit::log($event, 'event.closed', 'Voting was closed early');
        } else {
            if ((string) $before['starts_at'] !== (string) $event->starts_at) {
                Audit::log($event, 'event.window', "Opening time changed: {$fmt($before['starts_at'])} → {$fmt($event->starts_at)}");
            }
            if ((string) $before['ends_at'] !== (string) $event->ends_at) {
                Audit::log($event, 'event.window', "Closing time changed: {$fmt($before['ends_at'])} → {$fmt($event->ends_at)}");
            }
        }
        if ($before['name'] !== $event->name) {
            Audit::log($event, 'event.renamed', "Event renamed: “{$before['name']}” → “{$event->name}”");
        }
        if ($before['voting_mode'] !== $event->voting_mode) {
            Audit::log($event, 'event.mode', $event->isPaid() ? 'Switched to paid public voting' : 'Switched to voting with codes');
        }
        if ($event->isPaid() && (int) $before['vote_price_naira'] !== (int) $event->vote_price_naira) {
            Audit::log($event, 'event.price', "Price per vote set to ₦" . number_format((int) $event->vote_price_naira));
        }
        if ($before['results_visibility'] !== $event->results_visibility) {
            Audit::log($event, 'event.visibility', 'Results visibility changed: '
                . self::VISIBILITY_LABELS[$before['results_visibility']] . ' → ' . self::VISIBILITY_LABELS[$event->results_visibility]);
        }
        foreach (['self_registration' => 'Voter self-registration', 'ussd_enabled' => 'USSD voting', 'nominations_enabled' => 'Nominations'] as $field => $label) {
            if ((bool) ($before[$field] ?? false) !== (bool) $event->{$field}) {
                Audit::log($event, 'event.feature', $label . ($event->{$field} ? ' switched on' : ' switched off'));
            }
        }
        foreach (['weighted_votes' => 'Weighted votes', 'proxies_enabled' => 'Proxy voting'] as $field => $label) {
            if ((bool) $before[$field] !== (bool) $event->{$field}) {
                Audit::log($event, 'event.rules', $label . ($event->{$field} ? ' switched on' : ' switched off'));
            }
        }
        if ($event->proxies_enabled && (int) $before['max_proxies'] !== (int) $event->max_proxies) {
            Audit::log($event, 'event.rules', "Proxies one member may hold: {$event->max_proxies}");
        }
        if ($before['quorum_type'] !== $event->quorum_type || (int) $before['quorum_value'] !== (int) $event->quorum_value) {
            Audit::log($event, 'event.rules', $event->quorum_type ? 'Quorum set: ' . MeetingRules::describeQuorum($event) : 'Quorum removed');
        }
        if ((string) $before['reminder_at'] !== (string) $event->reminder_at) {
            Audit::log($event, 'event.reminder', $event->reminder_at
                ? "Reminder scheduled for {$fmt($event->reminder_at)}"
                : 'Scheduled reminder cancelled');
        }
    }

    /**
     * Weighted votes, proxies and quorum: Pro, codes voting only, and fixed once voting
     * starts (changing them mid-vote would change how votes already cast count).
     * The proxy limit can still change: it only affects proxies given from then on.
     */
    private function checkMeetingRules(Event $event, array $data): array
    {
        if (array_key_exists('quorum_type', $data) && ! $data['quorum_type']) {
            $data['quorum_type'] = null;
            $data['quorum_value'] = null;
        }
        $changed = fn (string $key) => array_key_exists($key, $data) && $data[$key] != $event->{$key};
        $ruleKeys = ['weighted_votes', 'proxies_enabled', 'quorum_type', 'quorum_value'];
        if (! collect($ruleKeys)->contains($changed)) {
            return $data;
        }
        $turningOn = (! empty($data['weighted_votes']) && ! $event->weighted_votes)
            || (! empty($data['proxies_enabled']) && ! $event->proxies_enabled)
            || (! empty($data['quorum_type']) && ! $event->quorum_type);
        if ($turningOn) {
            Plans::ensureMeetingRules($event);
        }
        if ($event->ballot_locked) {
            throw ValidationException::withMessages(['meeting_rules' => ['Weighted votes, proxies and quorum can’t be changed after voting has started.']]);
        }
        if ($event->isPaid()) {
            throw ValidationException::withMessages(['meeting_rules' => ['Weighted votes, proxies and quorum work with voter codes, not paid voting.']]);
        }

        $type = array_key_exists('quorum_type', $data) ? $data['quorum_type'] : $event->quorum_type;
        $value = array_key_exists('quorum_value', $data) ? $data['quorum_value'] : $event->quorum_value;
        if ($type && ! $value) {
            throw ValidationException::withMessages(['quorum_value' => ['Enter the quorum: a percentage or a number.']]);
        }
        if ($type === 'percent' && $value > 100) {
            throw ValidationException::withMessages(['quorum_value' => ['A quorum percentage must be between 1 and 100.']]);
        }

        // Switching proxies off cancels the proxies already given (nobody has voted yet).
        if (array_key_exists('proxies_enabled', $data) && ! $data['proxies_enabled'] && $event->proxies_enabled) {
            $n = Voter::where('event_id', $event->id)->whereNotNull('proxy_voter_id')->update(['proxy_voter_id' => null]);
            if ($n) {
                Audit::log($event, 'voters.proxy', "{$n} prox" . ($n === 1 ? 'y' : 'ies') . ' cancelled (proxy voting switched off)');
            }
        }
        return $data;
    }

    /** A 6-digit event number for USSD (no leading zero, unique). */
    private static function newUssdCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (Event::where('ussd_code', $code)->exists());
        return $code;
    }

    private function storeLogo(UploadedFile $file): string
    {
        $ext = $file->extension() ?: 'png';
        $filename = Str::lower(Str::random(16)) . '.' . $ext;
        $dest = public_path(self::BRAND_DIR);
        Filesystem::ensureDirectoryExists($dest);
        $file->move($dest, $filename);

        return self::BRAND_DIR . '/' . $filename;
    }

    private function deleteLogo(?string $path): void
    {
        if ($path && str_starts_with($path, self::BRAND_DIR . '/') && Filesystem::exists(public_path($path))) {
            Filesystem::delete(public_path($path));
        }
    }
}
