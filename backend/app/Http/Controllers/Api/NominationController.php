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
    private const MAX_SUGGESTIONS = 300;
    /** Same IP address, many browsers: a generous cap per category (shared mobile networks). */
    private const PER_NETWORK_PER_CATEGORY = 30;

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
            'language' => $event->language,
            'brand' => ($event->brand_color || $event->brand_logo_path) && \App\Support\Plans::isPro($event)
                ? ['color' => $event->brand_color, 'logo_url' => $event->brand_logo_url] : null,
            'categories' => $event->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'suggestions' => $c->nominee_suggestions ?? []]),
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
            // Anonymous ID the browser keeps (see store): random, never personal.
            'device_id' => ['nullable', 'string', 'max:100'],
        ]);
        Turnstile::verify($request);

        $event = Event::publicToken($token)->firstOrFail();
        if (! $event->nominationsOpen()) {
            return response()->json(['message' => __('Nominations are closed for this event.')], 403);
        }
        $category = Category::where('event_id', $event->id)->find($data['category_id']);
        if (! $category) {
            throw ValidationException::withMessages(['category_id' => [__('Choose a category from this event.')]]);
        }

        // "The same person" = same IP address AND same browser. Mobile networks share one IP
        // among many customers, so the IP alone would block strangers; the browser's
        // anonymous ID tells them apart. No ID (storage blocked): the IP alone decides.
        $key = (string) config('app.key');
        $ipHash = hash_hmac('sha256', (string) $request->ip(), $key);
        $deviceHash = ! empty($data['device_id']) ? hash_hmac('sha256', 'device:' . $data['device_id'], $key) : null;
        $samePerson = fn () => Nomination::where('category_id', $category->id)->where('ip_hash', $ipHash)
            ->when($deviceHash, fn ($q) => $q->where('device_hash', $deviceHash));

        $name = trim(preg_replace('/\s+/u', ' ', $data['nominee_name']) ?? '');

        // One nomination of a name per category per person (the same name in another category is fine).
        if ($samePerson()->where('normalized_name', Nomination::normalize($name))->exists()) {
            throw ValidationException::withMessages(['nominee_name' => [
                __('You’ve already nominated :name in “:category”. You can nominate them in another category.', ['name' => $name, 'category' => $category->name]),
            ]]);
        }
        if ($samePerson()->count() >= self::PER_PERSON_PER_CATEGORY) {
            return response()->json(['message' => __('You’ve already sent the most nominations allowed for this category.')], 429);
        }
        // Backstop against one network flooding a category (many browsers, one address).
        if (Nomination::where('category_id', $category->id)->where('ip_hash', $ipHash)->count() >= self::PER_NETWORK_PER_CATEGORY) {
            return response()->json(['message' => __('Too many nominations from this network for this category. Please try again later.')], 429);
        }
        Nomination::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'nominee_name' => $name,
            'normalized_name' => Nomination::normalize($name),
            'reason' => isset($data['reason']) ? trim($data['reason']) ?: null : null,
            'nominator_email' => isset($data['email']) ? strtolower($data['email']) : null,
            'ip_hash' => $ipHash,
            'device_hash' => $deviceHash,
        ]);

        return response()->json(['message' => __('Thank you! Your nomination of :name for “:category” was received.', ['name' => $name, 'category' => $category->name])], 201);
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
                'suggestions' => $cat->nominee_suggestions ?? [],
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

    /**
     * Organiser: the names suggested in a category's nomination form. The public
     * can pick one or type their own; same names (any spacing or case) count together.
     */
    public function suggestions(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $data = $request->validate([
            'category_id' => ['required', 'integer'],
            'names' => ['present', 'array', 'max:' . self::MAX_SUGGESTIONS],
            'names.*' => ['nullable', 'string', 'max:120'],
        ]);
        $category = Category::where('event_id', $event->id)->findOrFail($data['category_id']);

        $names = [];
        foreach ($data['names'] as $raw) {
            $name = trim(preg_replace('/\s+/u', ' ', (string) $raw) ?? '');
            if (mb_strlen($name) >= 2) $names[Nomination::normalize($name)] ??= $name; // first spelling wins
        }
        $names = array_values($names);

        $category->nominee_suggestions = $names ?: null;
        if ($category->isDirty('nominee_suggestions')) {
            $category->save();
            Audit::log($event, 'nomination.suggestions', count($names)
                ? 'Suggested nominees for “' . $category->name . '”: ' . count($names) . ' name' . (count($names) === 1 ? '' : 's')
                : 'Suggested nominees for “' . $category->name . '” removed');
        }

        return response()->json(['category_id' => $category->id, 'suggestions' => $names]);
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
