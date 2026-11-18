<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    /** Counts shown on the dashboard for each event. */
    private function counts(): array
    {
        return [
            'categories',
            'candidates',
            'voters',
            'voters as voted_count' => fn ($q) => $q->whereNotNull('used_at'),
        ];
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

    private function typeIdFor(?string $key): ?int
    {
        return $key ? EventType::where('key', $key)->value('id') : null;
    }

    public function index(Request $request)
    {
        $events = Event::where('user_id', $request->user()->id)
            ->with('type')
            ->withCount($this->counts())
            ->latest()
            ->get();

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'selection_mode' => ['nullable', Rule::in(['single', 'multiple'])],
            'expected_voters' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);
        $this->ensureValidWindow($data['starts_at'] ?? null, $data['ends_at'] ?? null);

        $event = Event::create([
            'user_id' => $request->user()->id,
            'event_type_id' => $this->typeIdFor($data['selection_mode'] ?? 'single'),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'link_token' => Str::uuid()->toString(),
            'expected_voters' => $data['expected_voters'] ?? 0,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        return response()->json($event->load('type')->loadCount($this->counts()), 201);
    }

    public function show(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        // Voters are fetched separately (GET /events/{id}/voters): an event can have thousands.
        $event->load(['type', 'categories', 'candidates'])->loadCount($this->counts());
        return response()->json($event);
    }

    public function update(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'selection_mode' => ['sometimes', Rule::in(['single', 'multiple'])],
            'expected_voters' => ['sometimes', 'integer', 'min:0'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
        ]);
        // Compare against the stored value for whichever bound was not sent.
        $this->ensureValidWindow(
            array_key_exists('starts_at', $data) ? $data['starts_at'] : $event->starts_at,
            array_key_exists('ends_at', $data) ? $data['ends_at'] : $event->ends_at,
        );

        if (array_key_exists('selection_mode', $data)) {
            $data['event_type_id'] = $this->typeIdFor($data['selection_mode']);
            unset($data['selection_mode']);
        }

        $event->update($data);
        return response()->json($event->fresh()->load('type')->loadCount($this->counts()));
    }

    public function destroy(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $event->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function link(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $frontend = rtrim(config('app.frontend_url'), '/');
        return response()->json([
            'token' => $event->link_token,
            'public_url' => "{$frontend}/vote/{$event->link_token}",
            'results_url' => "{$frontend}/event/{$event->link_token}/results",
        ]);
    }

    public function publicShow(string $token)
    {
        $event = Event::with('type')->where('link_token', $token)->firstOrFail();

        // Not open yet / already closed: tell the client why, without exposing the ballot.
        if (! $event->isOpen()) {
            return response()->json([
                'message' => $event->status === Event::STATUS_UPCOMING
                    ? 'This election has not started yet.'
                    : 'This election has ended.',
                'status' => $event->status,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
            ], 403);
        }

        $event->load([
            'categories:id,event_id,name',
            'candidates:id,event_id,category_id,name,image_path',
        ]);

        return response()->json([
            'id' => $event->id,
            'name' => $event->name,
            'description' => $event->description,
            'starts_at' => $event->starts_at,
            'ends_at' => $event->ends_at,
            'status' => $event->status,
            'selection_mode' => $event->selection_mode,
            'categories' => $event->categories,
            'candidates' => $event->candidates,
        ]);
    }

    public function results(string $token)
    {
        $event = Event::where('link_token', $token)->firstOrFail();

        $tally = DB::table('votes')
            ->where('event_id', $event->id)
            ->select('candidate_id', DB::raw('COUNT(*) as votes'))
            ->groupBy('candidate_id')
            ->pluck('votes', 'candidate_id');

        $categories = $event->categories()->with('candidates:id,category_id,name,image_path')->orderBy('id')->get();

        $results = $categories->map(function ($category) use ($tally) {
            $candidates = $category->candidates
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'image_path' => $c->image_path,
                    'image_url' => $c->image_url,
                    'votes' => (int) ($tally[$c->id] ?? 0),
                ])
                ->sortByDesc('votes')
                ->values();

            return [
                'id' => $category->id,
                'name' => $category->name,
                'candidates' => $candidates,
                'leader' => $candidates->first(),
            ];
        });

        return response()->json([
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'status' => $event->status,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
            ],
            'stats' => [
                'total_votes' => (int) $tally->sum(),
                'total_voters' => $event->voters()->count(),
                'voted' => $event->voters()->whereNotNull('used_at')->count(),
                'total_candidates' => $event->candidates()->count(),
            ],
            'categories' => $results,
        ]);
    }
}
