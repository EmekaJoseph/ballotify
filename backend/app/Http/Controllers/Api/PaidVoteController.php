<?php

namespace App\Http\Controllers\Api;

use App\Events\VoteCast;
use App\Http\Controllers\Controller;
use App\Mail\VotePurchaseReceipt;
use App\Models\Candidate;
use App\Models\Event;
use App\Models\VotePurchase;
use App\Support\Paystack;
use App\Support\Plans;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Paid public voting (award nights, pageants): anyone picks a candidate, pays
 * on Paystack for N votes, and the votes are added once Paystack confirms.
 *
 *  1. POST /public/events/{token}/paid-votes  -> Paystack checkout URL
 *  2. Paystack sends the voter back to /vote/{token}?ref=...
 *  3. POST /public/paid-votes/verify          -> votes added (idempotent)
 *  4. Paystack's webhook also lands in settle() (covers closed tabs)
 */
class PaidVoteController extends Controller
{
    public const PREFIX = 'PV-';
    private const MAX_QUANTITY = 10000;

    public function checkout(Request $request, string $token)
    {
        $data = $request->validate([
            'candidate_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:' . self::MAX_QUANTITY],
            'email' => ['required', 'email:rfc', 'max:255'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);
        $event = Event::publicToken($token)->firstOrFail();

        if (! $event->isPaid()) {
            return response()->json(['message' => 'This event doesn’t use paid voting.'], 422);
        }
        if (! $event->isOpen()) {
            return response()->json(['message' => $event->status === Event::STATUS_UPCOMING ? 'Voting has not started yet.' : 'Voting has ended.'], 403);
        }
        if (! Paystack::configured()) {
            return response()->json(['message' => 'Paid voting isn’t available right now. Please try again later.', 'payments_enabled' => false], 503);
        }

        $candidate = Candidate::where('event_id', $event->id)->find($data['candidate_id']);
        if (! $candidate) {
            throw ValidationException::withMessages(['candidate_id' => ['Choose a candidate from this event.']]);
        }

        $amountKobo = $data['quantity'] * (int) $event->vote_price_naira * 100;
        $min = (int) config('ballotify.billing.min_vote_payment_naira', 50);
        if ($amountKobo < $min * 100) {
            $need = (int) ceil($min / max(1, (int) $event->vote_price_naira));
            throw ValidationException::withMessages(['quantity' => ["The smallest payment is ₦{$min}. Choose at least {$need} votes."]]);
        }

        $purchase = VotePurchase::create([
            'event_id' => $event->id,
            'candidate_id' => $candidate->id,
            'candidate_name' => $candidate->name,
            'quantity' => $data['quantity'],
            'amount_kobo' => $amountKobo,
            'fee_kobo' => Plans::voteFeeKobo($amountKobo),
            'currency' => config('ballotify.billing.currency', 'NGN'),
            'reference' => self::PREFIX . Str::upper(Str::random(14)),
            'email' => strtolower($data['email']),
            'name' => $data['name'] ?? null,
            'status' => VotePurchase::PENDING,
        ]);

        $callback = rtrim(config('app.frontend_url'), '/') . "/vote/{$event->link_token}?ref={$purchase->reference}";

        try {
            $url = Paystack::initialize($purchase->email, $amountKobo, $purchase->reference, $callback, [
                'kind' => 'paid_vote',
                'event_id' => $event->id,
                'custom_fields' => [
                    ['display_name' => 'Event', 'variable_name' => 'event', 'value' => $event->name],
                    ['display_name' => 'Votes', 'variable_name' => 'votes', 'value' => "{$purchase->quantity} for {$candidate->name}"],
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            $purchase->update(['status' => VotePurchase::FAILED, 'failure_reason' => 'Could not start checkout']);
            return response()->json(['message' => 'We couldn’t reach the payment provider. Please try again in a moment.'], 502);
        }

        return response()->json(['authorization_url' => $url, 'reference' => $purchase->reference]);
    }

    /** Called by the ballot page after Paystack sends the voter back. */
    public function verify(Request $request)
    {
        $data = $request->validate(['reference' => ['required', 'string', 'max:64']]);
        $purchase = VotePurchase::where('reference', strtoupper(trim($data['reference'])))->firstOrFail();

        if ($purchase->status === VotePurchase::PENDING) {
            try {
                self::settle($purchase, Paystack::verify($purchase->reference));
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['status' => 'pending', 'message' => 'We couldn’t confirm the payment yet. If you were charged, your votes will be added automatically within a few minutes.'], 202);
            }
        }
        $purchase->refresh();

        return response()->json([
            'status' => $purchase->status,
            'reference' => $purchase->reference,
            'quantity' => $purchase->quantity,
            'candidate' => $purchase->candidate_name,
            'message' => match ($purchase->status) {
                VotePurchase::SUCCESS => "{$purchase->quantity} vote" . ($purchase->quantity === 1 ? '' : 's') . " for {$purchase->candidate_name} counted.",
                VotePurchase::LATE => 'Your payment arrived after voting closed, so the votes could not be counted. The organiser will arrange a refund.',
                VotePurchase::FAILED => $purchase->failure_reason ?: 'The payment was not completed, so no votes were added.',
                default => 'Your payment is still being processed.',
            },
        ]);
    }

    /**
     * Apply a verified Paystack transaction exactly once. Votes are added only
     * for a successful payment of the full amount made while voting was open.
     */
    public static function settle(VotePurchase $purchase, array $tx): void
    {
        $counted = DB::transaction(function () use ($purchase, $tx) {
            $purchase = VotePurchase::whereKey($purchase->id)->lockForUpdate()->first();
            if (! $purchase || $purchase->status !== VotePurchase::PENDING) {
                return null; // already settled
            }

            $status = $tx['status'] ?? null;
            if ($status === 'success') {
                if (($tx['reference'] ?? null) !== $purchase->reference
                    || (int) ($tx['amount'] ?? 0) < $purchase->amount_kobo
                    || strtoupper((string) ($tx['currency'] ?? '')) !== $purchase->currency) {
                    $purchase->update(['status' => VotePurchase::FAILED, 'failure_reason' => 'Amount or currency did not match. Contact the organiser.']);
                    Log::error('Paystack amount mismatch (paid vote)', ['reference' => $purchase->reference]);
                    return null;
                }

                $paidAt = isset($tx['paid_at']) ? Carbon::parse($tx['paid_at']) : now();
                $event = $purchase->event_id ? Event::find($purchase->event_id) : null;
                $closed = ! $event || ($event->ends_at && $paidAt->gt($event->ends_at));
                $candidateGone = ! $purchase->candidate_id || ! Candidate::whereKey($purchase->candidate_id)->exists();

                if ($closed || $candidateGone) {
                    $purchase->update([
                        'status' => VotePurchase::LATE, 'channel' => $tx['channel'] ?? null, 'paid_at' => $paidAt,
                        'failure_reason' => $closed ? 'Paid after voting closed' : 'Candidate no longer on the ballot',
                    ]);
                    return null;
                }

                Candidate::whereKey($purchase->candidate_id)->increment('votes', $purchase->quantity);
                $purchase->update(['status' => VotePurchase::SUCCESS, 'channel' => $tx['channel'] ?? null, 'paid_at' => $paidAt]);

                return $event;
            }

            if (in_array($status, ['failed', 'reversed'], true)) {
                $purchase->update(['status' => VotePurchase::FAILED, 'failure_reason' => $tx['gateway_response'] ?? ucfirst((string) $status)]);
            }
            // Abandoned / ongoing: stays pending so the voter can finish paying.
            return null;
        });

        if ($counted instanceof Event) {
            $purchase->refresh();
            // After the response, so the voter isn't kept waiting on the mail server.
            dispatch(function () use ($purchase, $counted) {
                // One receipt per payment, whatever runs this twice.
                if (! Cache::add("receipt-mail:{$purchase->reference}", true, now()->addDay())) {
                    return;
                }
                try {
                    Mail::to($purchase->email, $purchase->name)->send(new VotePurchaseReceipt($purchase, $counted));
                } catch (\Throwable $e) {
                    report($e);
                }
            })->afterResponse();
            if ($counted->resultsArePublic()) {
                try {
                    event(new VoteCast($counted->id));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }
    }
}
