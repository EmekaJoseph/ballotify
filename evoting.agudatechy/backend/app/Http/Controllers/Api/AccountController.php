<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Data rights: download everything about the account, or delete it. */
class AccountController extends Controller
{
    public function export(Request $request)
    {
        $user = $request->user();
        $events = Event::where('user_id', $user->id)->with(['categories', 'candidates'])->withCount('voters')->get();

        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'account' => $user->only(['name', 'email', 'email_verified_at', 'approved_at', 'created_at', 'two_factor_enabled']),
            'events' => $events->map(fn ($e) => $e->only(['name', 'description', 'starts_at', 'ends_at',
                'results_visibility', 'created_at', 'voters_count']) + [
                'categories' => $e->categories->pluck('name'),
                'candidates' => $e->candidates->map->only(['name', 'description']),
            ]),
        ])->header('Content-Disposition', 'attachment; filename="evoting-my-data.json"');
    }

    /** Delete the account and everything in it. */
    public function destroy(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password:sanctum']]);
        $user = $request->user();
        if ($user->is_admin) {
            throw ValidationException::withMessages(['password' => ['Platform admin accounts can’t be deleted here.']]);
        }

        // Uploaded photos aren't covered by the database cascade: remove them first.
        foreach (Event::where('user_id', $user->id)->with('candidates:id,event_id,image_path')->get(['id']) as $event) {
            foreach ($event->candidates as $candidate) {
                CandidateController::deleteImage($candidate->image_path);
            }
        }

        $user->tokens()->delete();
        $user->delete(); // events, voters, ballot totals and team links go with it (cascade)

        return response()->json(['message' => 'Your account has been deleted.']);
    }
}
