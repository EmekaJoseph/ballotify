<?php

namespace App\Http\Controllers;

use App\Models\Event;

abstract class Controller
{
    /**
     * Structural ballot changes (adding, removing or moving categories and
     * candidates) are refused once the first vote is cast or voting has closed.
     */
    protected function ensureBallotEditable(Event $event): void
    {
        if ($event->ballot_locked) {
            abort(response()->json([
                'message' => $event->status === Event::STATUS_COMPLETED
                    ? 'Voting has closed, so the ballot can no longer be changed.'
                    : 'Voting has started, so categories and candidates can no longer be added, removed or moved. You can still fix names and photos.',
                'locked' => true,
            ], 409));
        }
    }
}
