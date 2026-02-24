<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request, Event $event)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $category = Category::create([
            'event_id' => $event->id,
            'name' => $data['name'],
        ]);
        return response()->json($category, 201);
    }
}

