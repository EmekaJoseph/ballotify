<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Voter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VoterController extends Controller
{
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
                    'code_hash' => Hash::make($code),
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
        $voter = Voter::where('event_id', $event->id)->whereNull('used_at')->get();
        foreach ($voter as $v) {
            if (Hash::check($data['code'], $v->code_hash)) {
                return response()->json(['valid' => true]);
            }
        }
        return response()->json(['valid' => false], 422);
    }
}
