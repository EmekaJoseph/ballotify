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
            'shuffle_candidates' => ['sometimes', 'boolean'],
        ]);
        $category = Category::create([
            'event_id' => $event->id,
            'name' => $data['name'],
            'max_choices' => $request->exists('max_choices') ? $data['max_choices'] : 1,
            'shuffle_candidates' => (bool) ($data['shuffle_candidates'] ?? false),
            'method' => $method = $this->methodFor($event, $data['method'] ?? Category::PLURALITY),
        ]);
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
        ]);
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
        $limitChanged = $category->isDirty('max_choices') || $category->isDirty('method');
        $shuffleChanged = $category->isDirty('shuffle_candidates');
        $category->save();
        if ($limitChanged) {
            Audit::log($event, 'category.limit', "Voting method for “{$category->name}”: " . $category->methodLabel());
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

    /** Ranked choice is for secret ballots; paid voting is always "most votes wins". */
    private function methodFor(Event $event, string $method): string
    {
        if ($method === Category::RANKED && $event->isPaid()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['method' => ['Ranked choice works with voter codes, not paid voting.']]);
        }
        return $method;
    }
}
