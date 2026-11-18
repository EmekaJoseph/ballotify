<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File as Filesystem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class CandidateController extends Controller
{
    /** Candidate photos live in public/candidates so they work without `storage:link`. */
    private const IMAGE_DIR = 'candidates';

    public function index(Event $event)
    {
        Gate::authorize('manage', $event);
        return response()->json($event->candidates()->orderBy('category_id')->orderBy('id')->get());
    }

    public function store(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $data = $request->validate([
            'category_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', File::image()->max('5mb')],
        ]);

        $category = $this->categoryOf($event, (int) $data['category_id']);
        $this->ensureUniqueName($category->id, $data['name']);

        $candidate = Candidate::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => $data['name'],
            'image_path' => $request->hasFile('image') ? $this->storeImage($request->file('image')) : null,
        ]);

        return response()->json($candidate, 201);
    }

    public function update(Request $request, Event $event, Candidate $candidate)
    {
        Gate::authorize('manage', $event);
        $data = $request->validate([
            'category_id' => ['sometimes', 'integer'],
            'name' => ['sometimes', 'string', 'max:255'],
            'image' => ['nullable', File::image()->max('5mb')],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['category_id'])) {
            $candidate->category_id = $this->categoryOf($event, (int) $data['category_id'])->id;
        }
        if (isset($data['name'])) {
            $candidate->name = $data['name'];
        }
        if ($candidate->isDirty(['name', 'category_id'])) {
            $this->ensureUniqueName($candidate->category_id, $candidate->name, $candidate->id);
        }
        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            $this->deleteImage($candidate->image_path);
            $candidate->image_path = $request->hasFile('image') ? $this->storeImage($request->file('image')) : null;
        }

        $candidate->save();
        return response()->json($candidate);
    }

    public function destroy(Event $event, Candidate $candidate)
    {
        Gate::authorize('manage', $event);
        $this->deleteImage($candidate->image_path);
        $candidate->delete();
        return response()->json([], 204);
    }

    private function categoryOf(Event $event, int $categoryId): Category
    {
        $category = $event->categories()->find($categoryId);
        abort_if(! $category, 422, 'The selected category does not belong to this event.');
        return $category;
    }

    /** Friendly 422 instead of a database unique-index error. */
    private function ensureUniqueName(int $categoryId, string $name, ?int $ignoreId = null): void
    {
        $exists = Candidate::where('category_id', $categoryId)
            ->where('name', $name)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['name' => ['A candidate with this name already exists in that category.']]);
        }
    }

    private function storeImage(UploadedFile $file): string
    {
        // Use the extension detected from the file contents, never the client-supplied one.
        $ext = $file->extension() ?: 'jpg';
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'photo';
        $filename = Str::lower(Str::random(12)) . '_' . Str::limit($base, 40, '') . '.' . $ext;

        $dest = public_path(self::IMAGE_DIR);
        Filesystem::ensureDirectoryExists($dest);
        $file->move($dest, $filename);

        return self::IMAGE_DIR . '/' . $filename;
    }

    private function deleteImage(?string $path): void
    {
        if (! $path || ! str_starts_with($path, self::IMAGE_DIR . '/')) {
            return;
        }
        $full = public_path($path);
        if (Filesystem::exists($full)) {
            Filesystem::delete($full);
        }
    }
}
