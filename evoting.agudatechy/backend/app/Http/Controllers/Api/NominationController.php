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
            'brand' => $event->brand_logo_path ? ['logo_url' => $event->brand_logo_url] : null,
            'categories' => $event->categories
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'suggestions' => $c->nominee_suggestions ?? []]),
            // Names one person may put forward per category.
            'per_category' => self::PER_PERSON_PER_CATEGORY,
        ]);
    }

    /**
     * Public: the whole form at once (every category, several names each). One request,
     * so one "I'm human" check covers it. Each name is checked like a single nomination;
     * those refused are listed with the reason, the rest are kept.
     */
    public function storeMany(Request $request, string $token)
    {
        $data = $request->validate([
            'nominations' => ['required', 'array', 'min:1', 'max:300'],
            'nominations.*.category_id' => ['required', 'integer'],
            'nominations.*.nominee_name' => ['required', 'string', 'min:2', 'max:120'],
            'nominations.*.reason' => ['nullable', 'string', 'max:280'],
            'device_id' => ['nullable', 'string', 'max:100'],
        ], [
            'nominations.required' => __('Enter at least one name.'),
            'nominations.*.nominee_name.min' => __('Names need at least 2 letters.'),
        ]);
        Turnstile::verify($request);

        $event = Event::publicToken($token)->firstOrFail();
        if (! $event->nominationsOpen()) {
            return response()->json(['message' => __('Nominations are closed for this event.')], 403);
        }
        $categories = $this->nominatable($event)->keyBy('id');
        [$ipHash, $deviceHash] = $this->sameNominator($request, $data['device_id'] ?? null);

        $accepted = [];
        $refused = [];
        foreach ($data['nominations'] as $row) {
            $category = $categories->get($row['category_id']);
            $name = self::squish($row['nominee_name']);
            if (! $category) {
                $refused[] = ['category_id' => $row['category_id'], 'category' => null, 'name' => $name, 'reason' => __('Choose a category from this event.')];
                continue;
            }
            $no = $this->accept($event, $category, $name, $row['reason'] ?? null, $ipHash, $deviceHash);
            if ($no) {
                $refused[] = ['category_id' => $category->id, 'category' => $category->name, 'name' => $name, 'reason' => $no['message']];
            } else {
                $accepted[] = ['category_id' => $category->id, 'category' => $category->name, 'name' => $name];
            }
        }

        $n = count($accepted);
        return response()->json([
            'message' => $n
                ? trans_choice('Thank you! Your nomination was received.|Thank you! Your :count nominations were received.', $n, ['count' => $n])
                : __('None of these nominations could be sent.'),
            'accepted' => $accepted,
            'refused' => $refused,
        ], $n ? 201 : 422);
    }

    /** Categories people can nominate in. */
    private function nominatable(Event $event)
    {
        return $event->categories()->get();
    }

    /**
     * "The same person" = same IP address AND same browser. Mobile networks share one IP
     * among many customers, so the IP alone would block strangers; the browser's anonymous
     * ID tells them apart. No ID (storage blocked): the IP alone decides.
     *
     * @return array{0: string, 1: ?string} [ip hash, device hash]
     */
    private function sameNominator(Request $request, ?string $deviceId): array
    {
        $key = (string) config('app.key');
        return [
            hash_hmac('sha256', (string) $request->ip(), $key),
            $deviceId ? hash_hmac('sha256', 'device:' . $deviceId, $key) : null,
        ];
    }

    private static function squish(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name) ?? '');
    }

    /**
     * Save one nomination, or say why not (null = saved). The rules: a name once per
     * category per person, at most PER_PERSON_PER_CATEGORY names per category per person,
     * and a cap per network.
     *
     * @return array{status: int, message: string}|null
     */
    private function accept(Event $event, Category $category, string $rawName, ?string $reason, string $ipHash, ?string $deviceHash): ?array
    {
        $name = self::squish($rawName);
        $samePerson = fn () => Nomination::where('category_id', $category->id)->where('ip_hash', $ipHash)
            ->when($deviceHash, fn ($q) => $q->where('device_hash', $deviceHash));

        // The same name in another category is fine.
        if ($samePerson()->where('normalized_name', Nomination::normalize($name))->exists()) {
            return ['status' => 422, 'message' => __('You’ve already nominated :name in “:category”. You can nominate them in another category.', ['name' => $name, 'category' => $category->name])];
        }
        if ($samePerson()->count() >= self::PER_PERSON_PER_CATEGORY) {
            return ['status' => 429, 'message' => __('You’ve already sent the most nominations allowed for this category.')];
        }
        // Backstop against one network flooding a category (many browsers, one address).
        if (Nomination::where('category_id', $category->id)->where('ip_hash', $ipHash)->count() >= self::PER_NETWORK_PER_CATEGORY) {
            return ['status' => 429, 'message' => __('Too many nominations from this network for this category. Please try again later.')];
        }

        Nomination::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'nominee_name' => $name,
            'normalized_name' => Nomination::normalize($name),
            'reason' => $reason !== null ? (trim($reason) ?: null) : null,
            'ip_hash' => $ipHash,
            'device_hash' => $deviceHash,
        ]);
        return null;
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
