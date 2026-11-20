<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\Nomination;
use App\Support\Audit;
use App\Support\Turnstile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Nomination phase (award nights): the public suggests nominees per category,
 * then the organiser adds the most-nominated names to the ballot.
 */
class NominationController extends Controller
{
    private const PER_PERSON_PER_CATEGORY = 3;

    /** Public: the nomination form. */
    public function form(string $token)
    {
        $event = Event::publicToken($token)->with('categories')->firstOrFail();
        return response()->json([
            'name' => $event->name,
            'description' => $event->description,
            'open' => $event->nominationsOpen(),
            'ends_at' => $event->nominations_ends_at,
            'voting_starts_at' => $event->starts_at,
            'brand' => ($event->brand_color || $event->brand_logo_path) && \App\Support\Plans::isPro($event)
                ? ['color' => $event->brand_color, 'logo_url' => $event->brand_logo_url] : null,
            'categories' => $event->categories->map->only(['id', 'name']),
        ]);
    }

    /** Public: submit a nomination. */
    public function store(Request $request, string $token)
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer'],
            'nominee_name' => ['required', 'string', 'min:2', 'max:120'],
            'reason' => ['nullable', 'string', 'max:280'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
        ]);
        Turnstile::verify($request);

        $event = Event::publicToken($token)->firstOrFail();
        if (! $event->nominationsOpen()) {
            return response()->json(['message' => 'Nominations are closed for this event.'], 403);
        }
        $category = Category::where('event_id', $event->id)->find($data['category_id']);
        if (! $category) {
            throw ValidationException::withMessages(['category_id' => ['Choose a category from this event.']]);
        }

        $ipHash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $mine = Nomination::where('category_id', $category->id)->where('ip_hash', $ipHash)->count();
        if ($mine >= self::PER_PERSON_PER_CATEGORY) {
            return response()->json(['message' => 'You’ve already sent the most nominations allowed for this category.'], 429);
        }

        $name = trim(preg_replace('/\s+/u', ' ', $data['nominee_name']) ?? '');
        Nomination::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'nominee_name' => $name,
            'normalized_name' => Nomination::normalize($name),
            'reason' => isset($data['reason']) ? trim($data['reason']) ?: null : null,
            'nominator_email' => isset($data['email']) ? strtolower($data['email']) : null,
            'ip_hash' => $ipHash,
        ]);

        return response()->json(['message' => "Thank you! Your nomination of {$name} for “{$category->name}” was received."], 201);
    }

    /** Organiser: nominations grouped by category and name, most nominated first. */
    public function index(Event $event)
    {
        Gate::authorize('view', $event);
        $event->load(['categories', 'candidates']);
        $onBallot = $event->candidates->groupBy('category_id')
            ->map(fn ($c) => $c->map(fn ($x) => Nomination::normalize($x->name))->flip());

        $rows = Nomination::where('event_id', $event->id)->orderBy('created_at')->get()->groupBy('category_id');

        return response()->json([
            'open' => $event->nominationsOpen(),
            'enabled' => $event->nominations_enabled,
            'ends_at' => $event->nominations_ends_at,
            'total' => Nomination::where('event_id', $event->id)->count(),
            'categories' => $event->categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'nominees' => collect($rows[$cat->id] ?? [])->groupBy('normalized_name')
                    ->map(fn ($group, $key) => [
                        'name' => $group->countBy('nominee_name')->sortDesc()->keys()->first(), // most common spelling
                        'count' => $group->count(),
                        'reasons' => $group->pluck('reason')->filter()->reverse()->take(3)->values(),
                        'on_ballot' => isset($onBallot[$cat->id][$key]),
                    ])
                    ->sortByDesc('count')->values(),
            ]),
        ]);
    }

    /** Organiser: put a nominee on the ballot. */
    public function promote(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $this->ensureBallotEditable($event);
        $data = $request->validate(['category_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:255']]);
        $category = Category::where('event_id', $event->id)->findOrFail($data['category_id']);
        $name = trim($data['name']);

        $exists = Candidate::where('category_id', $category->id)->get(['name'])
            ->contains(fn ($c) => Nomination::normalize($c->name) === Nomination::normalize($name));
        if ($exists) {
            throw ValidationException::withMessages(['name' => ["{$name} is already on the ballot in “{$category->name}”."]]);
        }

        $candidate = Candidate::create(['event_id' => $event->id, 'category_id' => $category->id, 'name' => $name]);
        Audit::log($event, 'candidate.created', "Nominee “{$name}” added to “{$category->name}”");

        return response()->json($candidate, 201);
    }
}
