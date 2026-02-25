<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request, Event $event)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        $items = Category::where('event_id', $event->id)->orderBy('id')->get();
        return response()->json($items);
    }

    public function store(Request $request, Event $event)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->where('event_id', $event->id)],
        ]);
        $category = Category::create([
            'event_id' => $event->id,
            'name' => $data['name'],
        ]);
        return response()->json($category, 201);
    }

    public function update(Request $request, Event $event, Category $category)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        abort_unless($category->event_id === $event->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->where('event_id', $event->id)->ignore($category->id)],
        ]);
        $category->name = $data['name'];
        $category->save();
        return response()->json($category);
    }

    public function destroy(Request $request, Event $event, Category $category)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        abort_unless($category->event_id === $event->id, 404);
        $category->delete();
        return response()->json([], 204);
    }
}
