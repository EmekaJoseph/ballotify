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
        Gate::authorize('manage', $event);
        return response()->json(Category::where('event_id', $event->id)->orderBy('id')->get());
    }

    public function store(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $this->ensureBallotEditable($event);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->where('event_id', $event->id)],
            'max_choices' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);
        $category = Category::create([
            'event_id' => $event->id,
            'name' => $data['name'],
            'max_choices' => $data['max_choices'] ?? null,
        ]);
        Audit::log($event, 'category.created', "Category “{$category->name}” added");

        return response()->json($category, 201);
    }

    /**
     * Renaming stays possible during voting (typo fixes) and is audited.
     * The pick limit is part of the ballot's rules, so it locks with the ballot.
     */
    public function update(Request $request, Event $event, Category $category)
    {
        Gate::authorize('manage', $event);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('categories')->where('event_id', $event->id)->ignore($category->id)],
            'max_choices' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
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
        $limitChanged = $category->isDirty('max_choices');
        $category->save();
        if ($limitChanged) {
            Audit::log($event, 'category.limit', "Pick limit for “{$category->name}”: "
                . ($category->max_choices ? "up to {$category->max_choices}" : 'no limit'));
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
}
