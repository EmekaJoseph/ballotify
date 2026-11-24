<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendVoterInvitation;
use App\Models\Event;
use App\Models\Voter;
use App\Support\Plans;
use App\Support\Turnstile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Optional per event: voters get their own code by entering an ID that is on
 * the organiser's list (membership number, staff ID...). The code is only
 * ever sent to the email/phone the ORGANISER has on file for that ID, so
 * knowing someone's ID is not enough to vote as them.
 */
class SelfRegistrationController extends Controller
{
    public function requestCode(Request $request, string $token)
    {
        $data = $request->validate(['member_id' => ['required', 'string', 'max:64']]);
        Turnstile::verify($request);

        $event = Event::publicToken($token)->firstOrFail();
        $label = $event->member_id_label ?: 'ID';

        if ($event->isPaid() || ! $event->self_registration || ! Plans::isPro($event)) {
            return response()->json(['message' => __('This event doesn’t let voters request their own code. Ask the organiser for yours.')], 403);
        }
        if ($event->status === Event::STATUS_COMPLETED) {
            return response()->json(['message' => __('Voting has ended.')], 403);
        }

        $voter = Voter::where('event_id', $event->id)
            ->where('member_id', VoterController::normalizeMemberId($data['member_id']))
            ->first();

        if (! $voter) {
            return response()->json(['message' => __('That :label isn’t on the voter list. Check it, or contact the organiser.', ['label' => $label])], 404);
        }
        if ($voter->used_at) {
            return response()->json(['message' => __('This voter has already voted.')], 409);
        }
        if (! $voter->email && ! $voter->phone) {
            return response()->json(['message' => __('There’s no email or phone number on file for you. Please contact the organiser for your code.')], 422);
        }

        // At most one message per voter every 2 minutes.
        if (! Cache::add("self-reg:{$voter->id}", true, now()->addMinutes(2))) {
            return response()->json(['message' => __('We’ve just sent your code. Please wait a couple of minutes before asking again.')], 429);
        }

        $channels = array_values(array_filter([$voter->email ? 'email' : null, $voter->phone ? 'sms' : null]));
        // The SMS comes out of the event's SMS (or the organiser's credits); none left -> email only.
        $smsCharged = false;
        if ($voter->phone) {
            if (Plans::reserveSms($event, 1, false) === 1) {
                $smsCharged = ! Plans::smsUnlimited($event);
            } else {
                $channels = array_values(array_diff($channels, ['sms']));
            }
        }
        if (! $channels) {
            Cache::forget("self-reg:{$voter->id}");
            return response()->json(['message' => __('Your code can’t be sent by SMS right now. Please contact the organiser for your code.')], 503);
        }
        config('queue.default') === 'sync'
            ? SendVoterInvitation::dispatchAfterResponse($voter->id, $channels, 'invite', $smsCharged)
            : SendVoterInvitation::dispatch($voter->id, $channels, 'invite', $smsCharged);

        return response()->json([
            'message' => __('Your voting code is on its way.'),
            'sent_to' => array_values(array_filter([
                in_array('email', $channels, true) ? self::maskEmail($voter->email) : null,
                in_array('sms', $channels, true) ? self::maskPhone($voter->phone) : null,
            ])),
        ]);
    }

    private static function maskEmail(string $email): string
    {
        [$user, $domain] = explode('@', $email, 2) + [1 => ''];
        return mb_substr($user, 0, 2) . str_repeat('•', max(2, min(6, mb_strlen($user) - 2))) . '@' . $domain;
    }

    private static function maskPhone(string $phone): string
    {
        return mb_substr($phone, 0, 4) . str_repeat('•', max(3, strlen($phone) - 7)) . mb_substr($phone, -3);
    }
}
