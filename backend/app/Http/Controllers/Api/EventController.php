<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventType;
use App\Support\Audit;
use App\Support\Plans;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    private const VISIBILITY_LABELS = [
        Event::RESULTS_LIVE => 'live',
        Event::RESULTS_AFTER_CLOSE => 'after voting closes',
        Event::RESULTS_PRIVATE => 'organiser only',
    ];

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
        // All owned by this user: reuse it instead of loading it per event.
        $user = $request->user();
        $events->each(function (Event $e) use ($user) {
            $e->setRelation('user', $user);
            $e->setAttribute('limits', Plans::limits($e));
            $e->unsetRelation('user');
        });

        return response()->json($events);
    }

    public function store(Request $request)
    {
        Plans::ensureCanCreateEvent($request->user());
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'selection_mode' => ['nullable', Rule::in(['single', 'multiple'])],
            'results_visibility' => ['nullable', Rule::in(Event::RESULTS_OPTIONS)],
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
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'results_visibility' => $data['results_visibility'] ?? Event::RESULTS_AFTER_CLOSE,
        ]);
        Audit::log($event, 'event.created', "Event “{$event->name}” created");

        return response()->json(Plans::withLimits($event->load('type')->loadCount($this->counts())), 201);
    }

    public function show(Event $event)
    {
        Gate::authorize('manage', $event);
        // Voters are fetched separately (GET /events/{id}/voters): an event can have thousands.
        $event->load(['type', 'categories', 'candidates'])->loadCount($this->counts());
        return response()->json(Plans::withLimits($event));
    }

    public function update(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'selection_mode' => ['sometimes', Rule::in(['single', 'multiple'])],
            'results_visibility' => ['sometimes', Rule::in(Event::RESULTS_OPTIONS)],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
        ]);
        // Compare against the stored value for whichever bound was not sent.
        $this->ensureValidWindow(
            array_key_exists('starts_at', $data) ? $data['starts_at'] : $event->starts_at,
            array_key_exists('ends_at', $data) ? $data['ends_at'] : $event->ends_at,
        );

        $before = [
            'name' => $event->name,
            'selection_mode' => $event->selection_mode,
            'results_visibility' => $event->results_visibility,
            'starts_at' => $event->starts_at,
            'ends_at' => $event->ends_at,
            'status' => $event->status,
        ];

        if (array_key_exists('selection_mode', $data)) {
            if ($data['selection_mode'] !== $before['selection_mode'] && $event->ballot_locked) {
                throw ValidationException::withMessages(['selection_mode' => ['The voting style can’t be changed after voting has started.']]);
            }
            $data['event_type_id'] = $this->typeIdFor($data['selection_mode']);
            unset($data['selection_mode']);
        }

        $event->update($data);
        $event = $event->fresh()->load('type');
        $this->auditChanges($event, $before);

        return response()->json(Plans::withLimits($event->loadCount($this->counts())));
    }

    public function destroy(Event $event)
    {
        Gate::authorize('manage', $event);
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
        Plans::ensureCanCreateEvent($request->user());
        $event->load(['categories', 'candidates']);

        $copy = DB::transaction(function () use ($request, $event) {
            $copy = Event::create([
                'user_id' => $request->user()->id,
                'event_type_id' => $event->event_type_id,
                'name' => Str::limit($event->name, 245, '') . ' (copy)',
                'description' => $event->description,
                'link_token' => Str::uuid()->toString(),
                'results_visibility' => $event->results_visibility,
                'starts_at' => null,
                'ends_at' => null,
            ]);

            $categoryMap = [];
            foreach ($event->categories as $category) {
                $categoryMap[$category->id] = Category::create([
                    'event_id' => $copy->id,
                    'name' => $category->name,
                    'max_choices' => $category->max_choices,
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
                ]);
            }

            return $copy;
        });

        Audit::log($copy, 'event.created', "Event created as a copy of “{$event->name}”");

        return response()->json(Plans::withLimits($copy->load('type')->loadCount($this->counts())), 201);
    }

    public function link(Event $event)
    {
        Gate::authorize('manage', $event);
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
        Gate::authorize('manage', $event);
        $logs = $event->auditLogs()->with('user:id,name')->latest('created_at')->latest('id')->limit(500)->get();
        return response()->json($logs);
    }

    /** Organisers can always see the tally, whatever the public visibility setting. */
    public function adminResults(Event $event)
    {
        Gate::authorize('manage', $event);
        return response()->json($this->buildResults($event));
    }

    public function publicShow(string $token)
    {
        $event = Event::with('type')->publicToken($token)->firstOrFail();

        // Not open yet / already closed: tell the client why, without exposing the ballot.
        if (! $event->isOpen()) {
            return response()->json([
                'message' => $event->status === Event::STATUS_UPCOMING
                    ? 'This election has not started yet.'
                    : 'This election has ended.',
                'status' => $event->status,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
                'results_public' => $event->resultsArePublic(),
            ], 403);
        }

        $event->load([
            'categories:id,event_id,name,max_choices',
            'candidates:id,event_id,category_id,name,description,image_path',
        ]);

        return response()->json([
            'id' => $event->id,
            'name' => $event->name,
            'description' => $event->description,
            'starts_at' => $event->starts_at,
            'ends_at' => $event->ends_at,
            'status' => $event->status,
            'selection_mode' => $event->selection_mode,
            'results_visibility' => $event->results_visibility,
            // Free events show "Powered by Ballotify" on the ballot.
            'branding' => Plans::limits($event)['branding'],
            'categories' => $event->categories,
            'candidates' => $event->candidates,
        ]);
    }

    /** Public results page, subject to the event's results visibility. */
    public function results(string $token)
    {
        $event = Event::publicToken($token)->firstOrFail();

        if (! $event->resultsArePublic()) {
            return response()->json([
                'message' => $event->results_visibility === Event::RESULTS_PRIVATE
                    ? 'The organiser has chosen not to publish these results.'
                    : 'Results will be published when voting closes.',
                'visibility' => $event->results_visibility,
                'event' => [
                    'name' => $event->name,
                    'status' => $event->status,
                    'starts_at' => $event->starts_at,
                    'ends_at' => $event->ends_at,
                ],
            ], 403);
        }

        return response()->json($this->buildResults($event));
    }

    // ------------------------------------------------------------------ helpers

    private function buildResults(Event $event): array
    {
        $categories = $event->categories()
            ->with(['candidates' => fn ($q) => $q->select(['id', 'category_id', 'name', 'description', 'image_path', 'votes'])])
            ->orderBy('id')
            ->get();

        $totalVotes = 0;
        $results = $categories->map(function ($category) use (&$totalVotes) {
            $candidates = $category->candidates
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'description' => $c->description,
                    'image_url' => $c->image_url,
                    'votes' => (int) $c->votes,
                ])
                ->sortByDesc('votes')
                ->values();
            $totalVotes += $candidates->sum('votes');

            return [
                'id' => $category->id,
                'name' => $category->name,
                'candidates' => $candidates,
                'leader' => $candidates->first(),
            ];
        });

        return [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'status' => $event->status,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
                'results_visibility' => $event->results_visibility,
                'branding' => Plans::limits($event)['branding'],
            ],
            'stats' => [
                'total_votes' => $totalVotes,
                'total_voters' => $event->voters()->count(),
                'voted' => $event->voters()->whereNotNull('used_at')->count(),
                'total_candidates' => $event->candidates()->count(),
            ],
            // Transparency: how many organiser changes happened after voting began.
            'integrity' => [
                'changes_during_voting' => $event->auditLogs()->where('during_voting', true)->count(),
            ],
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
        if ($before['selection_mode'] !== $event->selection_mode) {
            Audit::log($event, 'event.mode', "Voting style changed to {$event->selection_mode} choice");
        }
        if ($before['results_visibility'] !== $event->results_visibility) {
            Audit::log($event, 'event.visibility', 'Results visibility changed: '
                . self::VISIBILITY_LABELS[$before['results_visibility']] . ' → ' . self::VISIBILITY_LABELS[$event->results_visibility]);
        }
    }
}
