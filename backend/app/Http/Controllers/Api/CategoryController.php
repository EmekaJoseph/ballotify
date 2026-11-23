<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Event $event)
    {
        Gate::authorize('view', $event);
        return response()->json(Category::where('event_id', $event->id)->orderBy('id')->get());
    }

    public function store(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $this->ensureBallotEditable($event);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->where('event_id', $event->id)],
            // 1 = choose one (default), N = up to N, null = no limit.
            'max_choices' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'method' => ['sometimes', Rule::in(Category::METHODS)],
            'description' => ['nullable', 'string', 'max:2000'],
            'pass_rule' => ['nullable', Rule::in(Category::PASS_RULES)],
            'groups' => ['nullable', 'array', 'max:20'],
            'shuffle_candidates' => ['sometimes', 'boolean'],
        ]);
        $category = Category::create([
            'event_id' => $event->id,
            'name' => $data['name'],
            'max_choices' => $request->exists('max_choices') ? $data['max_choices'] : 1,
            'shuffle_candidates' => (bool) ($data['shuffle_candidates'] ?? false),
            'method' => $method = $this->methodFor($event, $data['method'] ?? Category::PLURALITY),
            'description' => self::cleanText($data['description'] ?? null),
            'pass_rule' => $method === Category::MOTION ? ($data['pass_rule'] ?? 'simple') : null,
            'groups' => \App\Models\Voter::normalizeGroups($data['groups'] ?? []),
        ]);
        // A motion's options are fixed: Yes, No, Abstain (shown in the voter's language).
        if ($method === Category::MOTION) {
            $category->update(['max_choices' => 1]);
            foreach (['yes' => 'Yes', 'no' => 'No', 'abstain' => 'Abstain'] as $choice => $label) {
                \App\Models\Candidate::create(['event_id' => $event->id, 'category_id' => $category->id, 'name' => $label, 'motion_choice' => $choice]);
            }
        }
        // Ranking only one candidate isn't ranking: ranked categories default to "rank as many as you like".
        if ($method === Category::RANKED && $category->max_choices === 1) {
            $category->update(['max_choices' => null]);
        }
        Audit::log($event, 'category.created', "Category “{$category->name}” added");

        return response()->json($category->fresh(), 201);
    }

    /**
     * Renaming stays possible during voting (typo fixes) and is audited.
     * The pick limit is part of the ballot's rules, so it locks with the ballot.
     * Shuffling only changes display order, so it can change any time.
     */
    public function update(Request $request, Event $event, Category $category)
    {
        Gate::authorize('manage', $event);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('categories')->where('event_id', $event->id)->ignore($category->id)],
            'max_choices' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
            'shuffle_candidates' => ['sometimes', 'boolean'],
            'method' => ['sometimes', Rule::in(Category::METHODS)],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'pass_rule' => ['sometimes', Rule::in(Category::PASS_RULES)],
            'groups' => ['sometimes', 'nullable', 'array', 'max:20'],
        ]);
        // Motions keep their Yes / No / Abstain options: no switching to or from them.
        if (array_key_exists('method', $data) && ($data['method'] === Category::MOTION) !== $category->isMotion()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['method' => [$category->isMotion()
                ? 'A motion can’t become a candidate category. Delete it and add a category instead.'
                : 'Use “Add motion” to create a motion.']]);
        }
        $oldDescription = $category->description;
        if (array_key_exists('description', $data)) {
            $category->description = self::cleanText($data['description']);
        }
        if (array_key_exists('pass_rule', $data) && $category->isMotion()) {
            $category->pass_rule = $data['pass_rule'];
            if ($category->isDirty('pass_rule')) {
                $this->ensureBallotEditable($event);
            }
        }
        if (array_key_exists('groups', $data)) {
            $category->groups = \App\Models\Voter::normalizeGroups($data['groups'] ?? []);
            if ($category->isDirty('groups')) {
                $this->ensureBallotEditable($event);
            }
        }
        $old = $category->name;
        if (array_key_exists('name', $data)) {
            $category->name = $data['name'];
        }
        if (array_key_exists('max_choices', $data)) {
            $category->max_choices = $data['max_choices'];
            if ($category->isDirty('max_choices')) {
                $this->ensureBallotEditable($event);
            }
        }
        if (array_key_exists('method', $data)) {
            $category->method = $this->methodFor($event, $data['method']);
            if ($category->isDirty('method')) {
                $this->ensureBallotEditable($event);
            }
        }
        if (array_key_exists('shuffle_candidates', $data)) {
            $category->shuffle_candidates = (bool) $data['shuffle_candidates'];
        }
        if ($category->isRanked() && $category->max_choices === 1) {
            $category->max_choices = null;
        }
        $limitChanged = $category->isDirty('max_choices') || $category->isDirty('method') || $category->isDirty('pass_rule');
        $groupsChanged = $category->isDirty('groups');
        $descriptionChanged = $category->isDirty('description');
        $shuffleChanged = $category->isDirty('shuffle_candidates');
        $category->save();
        if ($limitChanged) {
            Audit::log($event, 'category.limit', "Voting method for “{$category->name}”: " . $category->methodLabel());
        }
        if ($groupsChanged) {
            Audit::log($event, 'category.groups', "Who can vote in “{$category->name}”: " . ($category->groups ? implode(', ', $category->groups) : 'everyone'));
        }
        if ($descriptionChanged) {
            Audit::log($event, 'category.description', "Text of “{$category->name}” changed" . ($oldDescription ? '' : ' (added)'));
        }
        if ($shuffleChanged) {
            Audit::log($event, 'category.shuffle', "Candidates in “{$category->name}” " . ($category->shuffle_candidates ? 'are shown in random order' : 'are shown in a fixed order'));
        }
        if ($old !== $category->name) {
            Audit::log($event, 'category.renamed', "Category renamed: “{$old}” → “{$category->name}”");
        }

        return response()->json($category);
    }

    public function destroy(Event $event, Category $category)
    {
        Gate::authorize('manage', $event);
        $this->ensureBallotEditable($event);
        $category->delete();
        Audit::log($event, 'category.deleted', "Category “{$category->name}” removed");

        return response()->json([], 204);
    }

    /** Ranked choice and motions are for secret ballots; paid voting is always "most votes wins". */
    private function methodFor(Event $event, string $method): string
    {
        if ($method !== Category::PLURALITY && $event->isPaid()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['method' => [$method === Category::MOTION
                ? 'Motions work with voter codes, not paid voting.'
                : 'Ranked choice works with voter codes, not paid voting.']]);
        }
        return $method;
    }

    private static function cleanText(?string $text): ?string
    {
        $text = trim(preg_replace("/[ \t]+/u", ' ', str_replace("\r", '', (string) $text)) ?? '');
        return $text === '' ? null : $text;
    }
}
