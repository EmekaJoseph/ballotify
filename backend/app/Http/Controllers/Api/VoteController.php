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
use Illuminate\Support\Facades\DB as FacadesDB;

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

        $matched = Voter::where('event_id', $event->id)
            ->whereNull('used_at')
            ->where('code', $data['code'])
            ->lockForUpdate()
            ->first();
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

        $typeKey = DB::table('event_types')->where('id', $event->event_type_id)->value('key') ?? 'single';
        if ($typeKey === 'single') {
            $byCategory = [];
            foreach ($data['choices'] as $choice) {
                $byCategory[$choice['category_id']] = ($byCategory[$choice['category_id']] ?? 0) + 1;
            }
            foreach ($byCategory as $count) {
                if ($count > 1) {
                    return response()->json(['message' => 'Only one candidate per category is allowed'], 422);
                }
            }
        }

        DB::transaction(function () use ($event, $matched, $data, $request, $typeKey) {
            $seen = [];
            foreach ($data['choices'] as $choice) {
                $key = $choice['category_id'] . ':' . $choice['candidate_id'];
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
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
