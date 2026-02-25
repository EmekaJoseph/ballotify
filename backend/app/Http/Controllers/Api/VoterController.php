<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Voter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VoterController extends Controller
{
    public function index(Request $request, Event $event)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        $voters = Voter::where('event_id', $event->id)
            ->orderBy('id')
            ->get();
        return response()->json($voters);
    }

    public function store(Request $request, Event $event)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'name' => ['nullable', 'string'],
            'code' => ['required', 'string', Rule::unique('voters', 'code')->where('event_id', $event->id)],
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
        abort_unless($event->user_id === $request->user()->id, 404);
        abort_unless($voter->event_id === $event->id, 404);
        $data = $request->validate([
            'name' => ['sometimes', 'nullable', 'string'],
            'code' => ['sometimes', 'string', Rule::unique('voters', 'code')->where('event_id', $event->id)->ignore($voter->id)],
            'used_at' => ['sometimes', 'nullable', 'date'],
        ]);
        if (array_key_exists('name', $data)) $voter->name = $data['name'];
        if (array_key_exists('code', $data)) $voter->code = $data['code'];
        if (array_key_exists('used_at', $data)) $voter->used_at = $data['used_at'];
        $voter->save();
        return response()->json($voter);
    }

    public function destroy(Request $request, Event $event, Voter $voter)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        abort_unless($voter->event_id === $event->id, 404);
        $voter->delete();
        return response()->json([], 204);
    }
    public function generate(Request $request, Event $event)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'names' => ['nullable', 'array'],
            'count' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $names = $data['names'] ?? [];
        $count = $data['count'] ?? 0;
        $toCreate = [];
        if ($count > 0 && empty($names)) {
            $names = array_fill(0, $count, null);
        }

        $plainCodes = [];

        DB::transaction(function () use ($event, $names, &$toCreate, &$plainCodes) {
            foreach ($names as $name) {
                $code = strtoupper(Str::random(8));
                $plainCodes[] = ['name' => $name, 'code' => $code];
                $toCreate[] = [
                    'event_id' => $event->id,
                    'name' => $name,
                    'code' => $code,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (! empty($toCreate)) {
                Voter::insert($toCreate);
            }
        });

        return response()->json(['voters' => $plainCodes], 201);
    }

    public function import(Request $request, Event $event)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'names' => ['required', 'array', 'min:1'],
            'names.*' => ['nullable', 'string'],
        ]);
        $request->merge(['count' => 0]);
        return $this->generate($request, $event);
    }

    public function validateCode(Request $request, string $token)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);
        $event = Event::where('link_token', $token)->firstOrFail();
        $exists = Voter::where('event_id', $event->id)
            ->whereNull('used_at')
            ->where('code', $data['code'])
            ->exists();
        return $exists
            ? response()->json(['valid' => true])
            : response()->json(['valid' => false], 422);
    }

    public function validateCodeByEvent(Request $request, Event $event)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);
        $exists = Voter::where('event_id', $event->id)
            ->whereNull('used_at')
            ->where('code', $data['code'])
            ->exists();
        return $exists
            ? response()->json(['valid' => true])
            : response()->json(['valid' => false], 422);
    }
}
