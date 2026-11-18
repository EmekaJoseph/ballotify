<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Support\Audit;
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
        $this->ensureBallotEditable($event);
        $data = $request->validate([
            'category_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:280'],
            'image' => ['nullable', File::image()->max('5mb')],
        ]);

        $category = $this->categoryOf($event, (int) $data['category_id']);
        $this->ensureUniqueName($category->id, $data['name']);

        $candidate = Candidate::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => $data['name'],
            'description' => self::cleanDescription($data['description'] ?? null),
            'image_path' => $request->hasFile('image') ? $this->storeImage($request->file('image')) : null,
        ]);
        Audit::log($event, 'candidate.created', "Candidate “{$candidate->name}” added to “{$category->name}”");

        return response()->json($candidate, 201);
    }

    public function update(Request $request, Event $event, Candidate $candidate)
    {
        Gate::authorize('manage', $event);
        $data = $request->validate([
            'category_id' => ['sometimes', 'integer'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:280'],
            'image' => ['nullable', File::image()->max('5mb')],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        $oldName = $candidate->name;
        if (isset($data['category_id']) && (int) $data['category_id'] !== $candidate->category_id) {
            // Moving a candidate changes the ballot's structure.
            $this->ensureBallotEditable($event);
            $candidate->category_id = $this->categoryOf($event, (int) $data['category_id'])->id;
        }
        if (isset($data['name'])) {
            $candidate->name = $data['name'];
        }
        if ($request->exists('description')) {
            $candidate->description = self::cleanDescription($data['description'] ?? null);
        }
        if ($candidate->isDirty(['name', 'category_id'])) {
            $this->ensureUniqueName($candidate->category_id, $candidate->name, $candidate->id);
        }
        $photoChanged = $request->hasFile('image') || $request->boolean('remove_image');
        if ($photoChanged) {
            $this->deleteImage($candidate->image_path);
            $candidate->image_path = $request->hasFile('image') ? $this->storeImage($request->file('image')) : null;
        }

        $movedCategory = $candidate->isDirty('category_id');
        $descriptionChanged = $candidate->isDirty('description');
        $candidate->save();

        if ($oldName !== $candidate->name) {
            Audit::log($event, 'candidate.renamed', "Candidate renamed: “{$oldName}” → “{$candidate->name}”");
        }
        if ($movedCategory) {
            Audit::log($event, 'candidate.moved', "Candidate “{$candidate->name}” moved to another category");
        }
        if ($descriptionChanged) {
            Audit::log($event, 'candidate.description', "Description of “{$candidate->name}” " . ($candidate->description ? 'updated' : 'removed'));
        }
        if ($photoChanged) {
            Audit::log($event, 'candidate.photo', "Photo of “{$candidate->name}” " . ($candidate->image_path ? 'changed' : 'removed'));
        }

        return response()->json($candidate);
    }

    public function destroy(Event $event, Candidate $candidate)
    {
        Gate::authorize('manage', $event);
        $this->ensureBallotEditable($event);
        $this->deleteImage($candidate->image_path);
        $candidate->delete();
        Audit::log($event, 'candidate.deleted', "Candidate “{$candidate->name}” removed");

        return response()->json([], 204);
    }

    /** Trim and collapse whitespace; an empty description is stored as null. */
    private static function cleanDescription(?string $text): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $text) ?? '');
        return $text === '' ? null : $text;
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
