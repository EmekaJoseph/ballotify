<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Payment;
use App\Support\Audit;
use App\Support\Paystack;
use App\Support\Plans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pay per event: a one-off Paystack payment upgrades one event to Pro.
 *
 *  1. POST /events/{id}/upgrade        -> Paystack checkout URL (organiser is redirected)
 *  2. Paystack redirects back to the dashboard with ?reference=...
 *  3. POST /billing/verify {reference} -> we ask Paystack, then upgrade the event
 *  4. Paystack also calls POST /paystack/webhook (covers closed tabs)
 *
 * Steps 3 and 4 both go through settle(), which is idempotent: an event is
 * upgraded once per payment, however many times either arrives.
 */
class BillingController extends Controller
{
    /** Public pricing (landing page). */
    public function publicPlans()
    {
        return response()->json($this->planInfo());
    }

    /** Pricing plus this organiser's account status (dashboard). */
    public function plans(Request $request)
    {
        return response()->json($this->planInfo() + [
            'account_pro' => Plans::accountIsPro($request->user()),
            'event_quota' => Plans::eventQuota($request->user()),
        ]);
    }

    public function checkout(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);

        if (! Plans::enabled() || Plans::isPro($event)) {
            return response()->json(['message' => 'This event is already Pro.'], 409);
        }
        if ($event->status === Event::STATUS_COMPLETED) {
            return response()->json(['message' => 'Voting has closed for this event, so it can no longer be upgraded.'], 422);
        }
        if (! Paystack::configured()) {
            return response()->json([
                'message' => 'Online payment isn’t available yet. Please contact us to upgrade this event.',
                'payments_enabled' => false,
            ], 503);
        }

        $payment = Payment::create([
            'user_id' => $request->user()->id,
            'event_id' => $event->id,
            'event_name' => $event->name,
            'reference' => 'BLT-' . $event->id . '-' . Str::upper(Str::random(12)),
            'amount_kobo' => Plans::priceKobo(),
            'currency' => config('ballotify.billing.currency', 'NGN'),
            'status' => Payment::PENDING,
        ]);

        $callback = rtrim(config('app.frontend_url'), '/') . "/dashboard/events/{$event->id}?tab=overview&payment=1";

        try {
            $url = Paystack::initialize($request->user()->email, $payment->amount_kobo, $payment->reference, $callback, [
                'event_id' => $event->id,
                'payment_id' => $payment->id,
                'custom_fields' => [['display_name' => 'Event', 'variable_name' => 'event', 'value' => $event->name]],
            ]);
        } catch (\Throwable $e) {
            report($e);
            $payment->update(['status' => Payment::FAILED, 'failure_reason' => 'Could not start checkout']);
            return response()->json(['message' => 'We couldn’t reach the payment provider. Please try again in a moment.'], 502);
        }

        return response()->json(['authorization_url' => $url, 'reference' => $payment->reference]);
    }

    /** Called by the dashboard after Paystack redirects back. */
    public function verify(Request $request)
    {
        $data = $request->validate(['reference' => ['required', 'string', 'max:64']]);
        $payment = Payment::where('reference', $data['reference'])->where('user_id', $request->user()->id)->firstOrFail();

        if ($payment->status === Payment::PENDING) {
            try {
                $this->settle($payment, Paystack::verify($payment->reference));
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['message' => 'We couldn’t confirm the payment yet. If you were charged, it will be applied automatically within a few minutes.', 'status' => 'pending'], 202);
            }
        }

        $payment->refresh();

        return response()->json([
            'status' => $payment->status,
            'message' => match ($payment->status) {
                Payment::SUCCESS => 'Payment received. This event is now Pro.',
                Payment::FAILED => $payment->failure_reason ?: 'The payment was not completed.',
                default => 'The payment is still being processed.',
            },
            'event_id' => $payment->event_id,
        ]);
    }

    /** Paystack server-to-server notification. Always answers 200 once the signature is valid. */
    public function webhook(Request $request)
    {
        $raw = $request->getContent();
        if (! Paystack::validSignature($raw, $request->header('x-paystack-signature'))) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = json_decode($raw, true) ?: [];
        if (($payload['event'] ?? null) !== 'charge.success') {
            return response()->json(['ok' => true]);
        }

        $reference = (string) ($payload['data']['reference'] ?? '');
        $payment = Payment::where('reference', $reference)->first();
        if (! $payment) {
            Log::warning('Paystack webhook for unknown reference', ['reference' => $reference]);
            return response()->json(['ok' => true]);
        }

        try {
            // Never trust the webhook body for amounts: ask Paystack directly.
            $this->settle($payment, Paystack::verify($reference));
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Retry later'], 500); // Paystack retries
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Apply a verified Paystack transaction to our payment record, exactly once.
     * The event is upgraded only if Paystack says success for the full amount.
     */
    private function settle(Payment $payment, array $tx): void
    {
        DB::transaction(function () use ($payment, $tx) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if (! $payment || $payment->status !== Payment::PENDING) {
                return; // already settled
            }

            $status = $tx['status'] ?? null;
            if ($status === 'success') {
                if (($tx['reference'] ?? null) !== $payment->reference
                    || (int) ($tx['amount'] ?? 0) < $payment->amount_kobo
                    || strtoupper((string) ($tx['currency'] ?? '')) !== $payment->currency) {
                    $payment->update(['status' => Payment::FAILED, 'failure_reason' => 'Amount or currency did not match. Contact support.']);
                    Log::error('Paystack amount mismatch', ['reference' => $payment->reference, 'tx' => $tx]);
                    return;
                }

                $payment->update([
                    'status' => Payment::SUCCESS,
                    'channel' => $tx['channel'] ?? null,
                    'paid_at' => now(),
                ]);

                $event = $payment->event_id ? Event::find($payment->event_id) : null;
                if ($event && $event->plan !== 'pro') {
                    $event->forceFill(['plan' => 'pro', 'upgraded_at' => now(), 'upgraded_via' => 'payment'])->save();
                    Audit::log($event, 'event.upgraded', 'Upgraded to Pro (payment ' . $payment->reference . ')');
                }
            } elseif (in_array($status, ['failed', 'reversed'], true)) {
                $payment->update(['status' => Payment::FAILED, 'failure_reason' => $tx['gateway_response'] ?? ucfirst((string) $status)]);
            }
            // Anything else (abandoned, ongoing, processing...) stays pending: the
            // organiser can still finish paying in the same checkout.
        });
    }

    private function planInfo(): array
    {
        $s = Plans::settings();

        return [
            'billing_enabled' => Plans::enabled(),
            'payments_enabled' => Plans::enabled() && Paystack::configured(),
            'currency' => config('ballotify.billing.currency', 'NGN'),
            'pro_price_naira' => $s['pro_price_naira'],
            'free' => [
                'max_active_events' => $s['free_max_active_events'],
                'max_voters' => $s['free_max_voters'],
                'messaging' => false,
                'branding' => true,
            ],
            'pro' => [
                'max_voters' => $s['pro_max_voters'],
                'messaging' => true,
                'branding' => false,
            ],
        ];
    }
}
