<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\Voter;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VoteController extends Controller
{
    public function store(Request $request, string $token)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'choices' => ['required', 'array', 'min:1'],
            'choices.*.category_id' => ['required', 'integer'],
            'choices.*.candidate_id' => ['required', 'integer'],
        ]);

        $event = Event::where('link_token', $token)->firstOrFail();

        $voter = Voter::where('event_id', $event->id)->whereNull('used_at')->lockForUpdate()->get();
        $matched = null;
        foreach ($voter as $v) {
            if (Hash::check($data['code'], $v->code_hash)) {
                $matched = $v;
                break;
            }
        }
        if (! $matched) {
            return response()->json(['message' => 'Invalid or used code'], 422);
        }

        $categoryIds = collect($data['choices'])->pluck('category_id')->all();
        $candidateIds = collect($data['choices'])->pluck('candidate_id')->all();

        $categories = Category::whereIn('id', $categoryIds)->where('event_id', $event->id)->get()->keyBy('id');
        $candidates = Candidate::whereIn('id', $candidateIds)->where('event_id', $event->id)->get()->keyBy('id');

        foreach ($data['choices'] as $choice) {
            if (! isset($categories[$choice['category_id']])) {
                return response()->json(['message' => 'Invalid category'], 422);
            }
            if (! isset($candidates[$choice['candidate_id']])) {
                return response()->json(['message' => 'Invalid candidate'], 422);
            }
            if ($candidates[$choice['candidate_id']]->category_id !== (int) $choice['category_id']) {
                return response()->json(['message' => 'Candidate not in category'], 422);
            }
        }

        DB::transaction(function () use ($event, $matched, $data, $request) {
            foreach ($data['choices'] as $choice) {
                Vote::create([
                    'event_id' => $event->id,
                    'voter_id' => $matched->id,
                    'category_id' => $choice['category_id'],
                    'candidate_id' => $choice['candidate_id'],
                    'ip' => $request->ip(),
                ]);
            }
            $matched->used_at = now();
            $matched->save();
        });

        return response()->json(['message' => 'Vote recorded']);
    }
}

