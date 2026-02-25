<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File as Filesystem;
use Illuminate\Support\Str;
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
            $file = $request->file('image');
            $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $ext = $file->getClientOriginalExtension();
            $filename = uniqid() . '_' . Str::slug($original) . '.' . $ext;
            $dest = public_path('candidates');
            Filesystem::ensureDirectoryExists($dest);
            $file->move($dest, $filename);
            $imagePath = 'candidates/' . $filename;
        }

        $candidate = Candidate::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => $data['name'],
            'image_path' => $imagePath,
        ]);

        return response()->json($candidate, 201);
    }

    public function update(Request $request, Event $event, Candidate $candidate)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        abort_unless($candidate->event_id === $event->id, 404);
        $data = $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'image' => ['nullable', File::image()->max('5mb')],
        ]);
        if (isset($data['category_id'])) {
            $category = Category::where('id', $data['category_id'])->where('event_id', $event->id)->firstOrFail();
            $candidate->category_id = $category->id;
        }
        if (isset($data['name'])) {
            $candidate->name = $data['name'];
        }
        if ($request->file('image')) {
            if ($candidate->image_path) {
                $old = public_path($candidate->image_path);
                if (Filesystem::exists($old)) {
                    Filesystem::delete($old);
                }
            }
            $file = $request->file('image');
            $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $ext = $file->getClientOriginalExtension();
            $filename = uniqid() . '_' . Str::slug($original) . '.' . $ext;
            $dest = public_path('candidates');
            Filesystem::ensureDirectoryExists($dest);
            $file->move($dest, $filename);
            $candidate->image_path = 'candidates/' . $filename;
        }
        $candidate->save();
        return response()->json($candidate);
    }

    public function destroy(Request $request, Event $event, Candidate $candidate)
    {
        abort_unless($event->user_id === $request->user()->id, 404);
        abort_unless($candidate->event_id === $event->id, 404);
        if ($candidate->image_path) {
            $path = public_path($candidate->image_path);
            if (Filesystem::exists($path)) {
                Filesystem::delete($path);
            }
        }
        $candidate->delete();
        return response()->json([], 204);
    }
}
