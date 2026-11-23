<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Event;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

/**
 * Optional: a private link a candidate uses to add their own photo and short
 * description (manifesto line). The name and category stay with the organiser.
 */
class CandidateProfileController extends Controller
{
    private function link(Candidate $candidate): string
    {
        return rtrim((string) config('app.frontend_url'), '/') . '/candidate/' . $candidate->profile_token;
    }

    /** Organiser: get (or create) the candidate's private link. */
    public function show(Event $event, Candidate $candidate)
    {
        Gate::authorize('manage', $event);
        abort_if((bool) $candidate->motion_choice, 422, 'Motion options don’t have profiles.');
        if (! $candidate->profile_token) {
            $candidate->forceFill(['profile_token' => Str::random(40)])->save();
            Audit::log($event, 'candidate.link', "Profile link created for “{$candidate->name}”");
        }
        return response()->json(['url' => $this->link($candidate)]);
    }

    /** Organiser: switch the link off (a new one can be made later). */
    public function revoke(Event $event, Candidate $candidate)
    {
        Gate::authorize('manage', $event);
        $candidate->forceFill(['profile_token' => null])->save();
        Audit::log($event, 'candidate.link', "Profile link for “{$candidate->name}” switched off");
        return response()->json([], 204);
    }

    private function byToken(string $token): Candidate
    {
        $candidate = Candidate::where('profile_token', $token)->with(['event.user', 'category'])->firstOrFail();
        abort_if(! $candidate->event || $candidate->event->user?->suspended_at, 404);
        return $candidate;
    }

    /** Candidate: what they can edit. */
    public function publicShow(string $token)
    {
        $c = $this->byToken($token);
        return response()->json([
            'id' => $c->id,
            'name' => $c->name,
            'description' => $c->description,
            'image_url' => $c->image_url,
            'category' => $c->category?->name,
            // The public voting link (for the campaign card's QR code) and brand colour.
            'event' => ['name' => $c->event->name, 'status' => $c->event->status, 'link_token' => $c->event->link_token,
                'brand_color' => \App\Support\Plans::isPro($c->event) ? $c->event->brand_color : null],
            'editable' => $c->event->status !== Event::STATUS_COMPLETED,
        ]);
    }

    /** Candidate: save their photo and/or description. Every change is in the activity log. */
    public function publicUpdate(Request $request, string $token)
    {
        $c = $this->byToken($token);
        abort_if($c->event->status === Event::STATUS_COMPLETED, 403, 'Voting has closed, so the profile can no longer be changed.');
        $request->validate([
            'description' => ['sometimes', 'nullable', 'string', 'max:280'],
            'image' => ['nullable', File::image()->max('5mb')],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        $changes = [];
        if ($request->exists('description')) {
            $c->description = CandidateController::cleanDescription($request->input('description'));
            if ($c->isDirty('description')) $changes[] = 'description';
        }
        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            CandidateController::deleteImage($c->image_path);
            $c->image_path = $request->hasFile('image') ? CandidateController::storeImage($request->file('image')) : null;
            $changes[] = 'photo';
        }
        $c->save();
        if ($changes) {
            Audit::log($c->event, 'candidate.self_edit', "“{$c->name}” updated their " . implode(' and ', $changes) . ' (candidate link)');
        }

        return response()->json(['name' => $c->name, 'description' => $c->description, 'image_url' => $c->image_url]);
    }
}
