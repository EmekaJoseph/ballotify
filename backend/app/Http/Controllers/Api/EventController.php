<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::where('user_id', $request->user()->id)->latest()->get();
        return response()->json($events);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'expected_voters' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $event = Event::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'link_token' => Str::uuid()->toString(),
            'expected_voters' => $data['expected_voters'] ?? 0,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        return response()->json($event, 201);
    }

    public function show(Request $request, Event $event)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        $event->load(['categories', 'candidates', 'voters']);
        return response()->json($event);
    }

    public function update(Request $request, Event $event)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'expected_voters' => ['sometimes', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
        $event->update($data);
        return response()->json($event);
    }

    public function destroy(Request $request, Event $event)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        $event->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function link(Request $request, Event $event)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        return response()->json([
            'token' => $event->link_token,
            'public_url' => url("/api/public/events/{$event->link_token}"),
        ]);
    }

    public function publicShow(string $token)
    {
        $now = now();
        $event = Event::where('link_token', $token)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->firstOrFail();
        $event->load(['categories', 'candidates' => function ($q) {
            $q->select(['id', 'event_id', 'category_id', 'name', 'image_path']);
        }]);
        $typeKey = DB::table('event_types')->where('id', $event->event_type_id)->value('key') ?? 'single';
        $event->setAttribute('selection_mode', $typeKey);
        return response()->json($event);
    }

    public function results(string $token)
    {
        $event = Event::where('link_token', $token)->firstOrFail();
        
        $totalVotes = DB::table('votes')->where('event_id', $event->id)->count();
        $totalCandidates = DB::table('candidates')->where('event_id', $event->id)->count();
        
        $categories = $event->categories()->get();
        $results = [];
        
        foreach ($categories as $category) {
            $candidates = DB::table('candidates')
                ->where('category_id', $category->id)
                ->select(['id', 'name', 'image_path'])
                ->get();
                
            $candidateResults = [];
            foreach ($candidates as $candidate) {
                $voteCount = DB::table('votes')
                    ->where('category_id', $category->id)
                    ->where('candidate_id', $candidate->id)
                    ->count();
                    
                $candidateResults[] = [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'image_path' => $candidate->image_path,
                    'votes' => $voteCount
                ];
            }
            
            // Sort by votes descending
            usort($candidateResults, fn($a, $b) => $b['votes'] <=> $a['votes']);
            
            $results[] = [
                'id' => $category->id,
                'name' => $category->name,
                'candidates' => $candidateResults,
                'leader' => $candidateResults[0] ?? null
            ];
        }
        
        return response()->json([
            'event' => [
                'name' => $event->name,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
            ],
            'stats' => [
                'total_votes' => $totalVotes,
                'total_candidates' => $totalCandidates,
            ],
            'categories' => $results
        ]);
    }
}
