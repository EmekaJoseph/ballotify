<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class CandidateController extends Controller
{
    public function store(Request $request, Event $event)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', File::image()->max('5mb')],
        ]);

        $category = Category::where('id', $data['category_id'])->where('event_id', $event->id)->firstOrFail();

        $imagePath = null;
        if ($request->file('image')) {
            $imagePath = $request->file('image')->store('candidates', 'public');
        }

        $candidate = Candidate::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => $data['name'],
            'image_path' => $imagePath,
        ]);

        return response()->json($candidate, 201);
    }
}

