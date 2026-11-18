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
        ]);
        $category = Category::create(['event_id' => $event->id, 'name' => $data['name']]);
        Audit::log($event, 'category.created', "Category “{$category->name}” added");

        return response()->json($category, 201);
    }

    /** Renaming stays possible during voting (typo fixes) and is audited. */
    public function update(Request $request, Event $event, Category $category)
    {
        Gate::authorize('manage', $event);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->where('event_id', $event->id)->ignore($category->id)],
        ]);
        $old = $category->name;
        $category->name = $data['name'];
        $category->save();
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
