<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\BallotBox;
use App\Support\BallotRejected;
use App\Support\CodeGuard;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    public function store(Request $request, string $token)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'choices' => ['required', 'array', 'min:1', 'max:500'],
            'choices.*.category_id' => ['required', 'integer'],
            'choices.*.candidate_id' => ['required', 'integer'],
        ]);

        $event = Event::publicToken($token)->firstOrFail();
        if ($blocked = CodeGuard::blocked($request, $event)) {
            return $blocked;
        }

        try {
            $receipt = BallotBox::cast($event, $data['code'], $data['choices']);
        } catch (BallotRejected $e) {
            if ($e->unknownCode) {
                CodeGuard::miss($request, $event);
            }
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        return response()->json(['message' => 'Vote recorded', 'receipt' => $receipt]);
    }
}
