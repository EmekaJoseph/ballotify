<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Vote;
use App\Models\Voter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VoterController extends Controller
{
    /** Unambiguous characters (no 0/O, 1/I/L) so printed codes are easy to type. */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const CODE_LENGTH = 8;

    private function randomCode(): string
    {
        $code = '';
        $max = strlen(self::CODE_ALPHABET) - 1;
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, $max)];
        }
        return $code;
    }

    /** Codes are case-insensitive for voters: store and compare them upper-cased and trimmed. */
    public static function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    public function index(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $voters = Voter::where('event_id', $event->id)
            ->orderBy('id')
            ->get();
        return response()->json($voters);
    }

    public function store(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $request->merge(['code' => self::normalizeCode((string) $request->input('code', ''))]);
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'min:4', 'max:64', Rule::unique('voters', 'code')->where('event_id', $event->id)],
        ]);
        $voter = Voter::create([
            'event_id' => $event->id,
            'name' => $data['name'] ?? null,
            'code' => $data['code'],
        ]);
        return response()->json($voter, 201);
    }

    public function update(Request $request, Event $event, Voter $voter)
    {
        Gate::authorize('manage', $event);
        if ($request->has('code')) {
            $request->merge(['code' => self::normalizeCode((string) $request->input('code'))]);
        }
        $data = $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'min:4', 'max:64', Rule::unique('voters', 'code')->where('event_id', $event->id)->ignore($voter->id)],
            'used_at' => ['sometimes', 'nullable', 'date'],
        ]);

        DB::transaction(function () use ($data, $voter) {
            if (array_key_exists('name', $data)) $voter->name = $data['name'];
            if (array_key_exists('code', $data)) $voter->code = $data['code'];
            if (array_key_exists('used_at', $data)) {
                // Re-enabling a code discards that voter's previous ballot so the
                // tally never counts them twice.
                if ($data['used_at'] === null && $voter->used_at !== null) {
                    Vote::where('voter_id', $voter->id)->delete();
                }
                $voter->used_at = $data['used_at'];
            }
            $voter->save();
        });

        return response()->json($voter);
    }

    public function destroy(Request $request, Event $event, Voter $voter)
    {
        Gate::authorize('manage', $event);
        $voter->delete();
        return response()->json([], 204);
    }

    public function generate(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $data = $request->validate([
            'names' => ['nullable', 'array', 'max:10000'],
            'names.*' => ['nullable', 'string', 'max:255'],
            'count' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $names = array_values($data['names'] ?? []);
        $count = $data['count'] ?? 0;
        if ($count > 0 && empty($names)) {
            $names = array_fill(0, $count, null);
        }
        if (empty($names)) {
            return response()->json(['message' => 'Provide a count or a list of names.'], 422);
        }

        $plainCodes = DB::transaction(function () use ($event, $names) {
            $taken = array_flip(Voter::where('event_id', $event->id)->pluck('code')->all());
            $rows = [];
            $out = [];
            $now = now();
            foreach ($names as $name) {
                do {
                    $code = $this->randomCode();
                } while (isset($taken[$code]));
                $taken[$code] = true;

                $name = is_string($name) && trim($name) !== '' ? trim($name) : null;
                $out[] = ['name' => $name, 'code' => $code];
                $rows[] = [
                    'event_id' => $event->id,
                    'name' => $name,
                    'code' => $code,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                Voter::insert($chunk);
            }
            return $out;
        });

        return response()->json(['voters' => $plainCodes], 201);
    }

    public function import(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $request->validate([
            'names' => ['required', 'array', 'min:1', 'max:10000'],
            'names.*' => ['nullable', 'string', 'max:255'],
        ]);
        $request->merge(['count' => null]);
        return $this->generate($request, $event);
    }

    public function validateCode(Request $request, string $token)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);
        $event = Event::where('link_token', $token)->firstOrFail();
        if (! $event->isOpen()) {
            return response()->json(['valid' => false, 'message' => 'This election is not open.'], 403);
        }
        $exists = Voter::where('event_id', $event->id)
            ->whereNull('used_at')
            ->where('code', self::normalizeCode($data['code']))
            ->exists();
        return $exists
            ? response()->json(['valid' => true])
            : response()->json(['valid' => false, 'message' => 'Invalid or used voting code.'], 422);
    }
}
