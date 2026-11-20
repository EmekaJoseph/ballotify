<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Mail\PaymentReceipt;
use App\Models\Payment;
use App\Models\VotePurchase;
use App\Support\Audit;
use App\Support\Flutterwave;
use App\Support\Payments;
use App\Support\Paystack;
use App\Support\Plans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Pay per event: a one-off payment upgrades one event to Pro. Paystack takes
 * it; Flutterwave is the backup when Paystack is down (see Payments).
 *
 *  1. POST /events/{id}/upgrade        -> checkout URL (organiser is redirected)
 *  2. The provider redirects back to the dashboard with ?reference=...
 *  3. POST /billing/verify {reference} -> we ask the provider, then upgrade the event
 *  4. The provider also calls its webhook (covers closed tabs)
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
        if (! Payments::available()) {
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
            [$provider, $url] = Payments::checkout($request->user()->email, $request->user()->name, $payment->amount_kobo, $payment->reference, $callback, [
                'event_id' => $event->id,
                'payment_id' => $payment->id,
                'custom_fields' => [['display_name' => 'Event', 'variable_name' => 'event', 'value' => $event->name]],
            ], [], "Pro upgrade: {$event->name}");
            $payment->update(['provider' => $provider]);
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
                $this->settle($payment, Payments::verify($payment->provider, $payment->reference));
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

        // Paid public votes share the same Paystack account and webhook.
        if (str_starts_with($reference, PaidVoteController::PREFIX)) {
            $purchase = VotePurchase::where('reference', $reference)->where('provider', Payments::PAYSTACK)->first();
            if (! $purchase) {
                Log::warning('Paystack webhook for unknown vote purchase', ['reference' => $reference]);
                return response()->json(['ok' => true]);
            }
            try {
                PaidVoteController::settle($purchase, Paystack::verify($reference));
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['message' => 'Retry later'], 500);
            }
            return response()->json(['ok' => true]);
        }

        $payment = Payment::where('reference', $reference)->where('provider', Payments::PAYSTACK)->first();
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
     * Flutterwave server-to-server notification (backup provider). Like
     * Paystack's: the body is only a hint; the transaction is verified with
     * Flutterwave's API before anything is applied.
     */
    public function flutterwaveWebhook(Request $request)
    {
        if (! Flutterwave::validWebhook($request->header('verif-hash'))) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $reference = (string) ($request->input('data.tx_ref') ?? $request->input('txRef') ?? '');
        $record = str_starts_with($reference, PaidVoteController::PREFIX)
            ? VotePurchase::where('reference', $reference)->where('provider', Payments::FLUTTERWAVE)->first()
            : Payment::where('reference', $reference)->where('provider', Payments::FLUTTERWAVE)->first();
        if (! $record) {
            Log::warning('Flutterwave webhook for unknown reference', ['reference' => $reference]);
            return response()->json(['ok' => true]);
        }

        try {
            $tx = Flutterwave::verify($reference);
            $record instanceof VotePurchase ? PaidVoteController::settle($record, $tx) : $this->settle($record, $tx);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Retry later'], 500); // Flutterwave retries
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Apply a verified transaction (Paystack's shape) to our payment record, exactly once.
     * The event is upgraded only if Paystack says success for the full amount.
     */
    private function settle(Payment $payment, array $tx): void
    {
        $upgraded = DB::transaction(function () use ($payment, $tx) {
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
                    Log::error('Payment amount mismatch', ['reference' => $payment->reference, 'provider' => $payment->provider, 'tx' => $tx]);
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
                return $payment;
            } elseif (in_array($status, ['failed', 'reversed'], true)) {
                $payment->update(['status' => Payment::FAILED, 'failure_reason' => $tx['gateway_response'] ?? ucfirst((string) $status)]);
            }
            // Anything else (abandoned, ongoing, processing...) stays pending: the
            // organiser can still finish paying in the same checkout.
            return null;
        });

        // Emailed receipt, after the response.
        if ($upgraded instanceof Payment && $upgraded->user) {
            dispatch(function () use ($upgraded) {
                // One receipt per payment, whatever runs this twice.
                if (! \Illuminate\Support\Facades\Cache::add("receipt-mail:{$upgraded->reference}", true, now()->addDay())) {
                    return;
                }
                try {
                    Mail::to($upgraded->user->email, $upgraded->user->name)->send(new PaymentReceipt($upgraded->fresh('user')));
                } catch (\Throwable $e) {
                    report($e);
                }
            })->afterResponse();
        }
    }

    /** The organiser's own payments (account page). */
    public function myPayments(Request $request)
    {
        return response()->json(
            Payment::where('user_id', $request->user()->id)->latest()->limit(100)
                ->get(['reference', 'event_id', 'event_name', 'amount_kobo', 'currency', 'status', 'channel', 'paid_at', 'created_at'])
                ->map(fn ($p) => $p->toArray() + ['amount_naira' => $p->amount_kobo / 100])
        );
    }

    /** One payment, for the printable receipt. */
    public function receipt(Request $request, string $reference)
    {
        $payment = Payment::where('reference', $reference)->where('user_id', $request->user()->id)
            ->where('status', Payment::SUCCESS)->firstOrFail();

        return response()->json([
            'reference' => $payment->reference,
            'item' => 'Ballotify Pro upgrade',
            'event_name' => $payment->event_name,
            'amount_naira' => $payment->amount_kobo / 100,
            'currency' => $payment->currency,
            'channel' => $payment->channel,
            'paid_at' => $payment->paid_at,
            'billed_to' => ['name' => $request->user()->name, 'email' => $request->user()->email],
            'seller' => ['name' => config('app.name', 'Ballotify'), 'email' => config('mail.from.address')],
        ]);
    }

    private function planInfo(): array
    {
        $s = Plans::settings();

        return [
            'billing_enabled' => Plans::enabled(),
            'payments_enabled' => Plans::enabled() && Payments::available(),
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
                'custom_branding' => true,
                'team' => true,
            ],
            // Paid public voting: the platform keeps this share of each vote payment.
            'paid_votes' => [
                'fee_percent' => $s['paid_vote_fee_percent'],
                'min_payment_naira' => (int) config('ballotify.billing.min_vote_payment_naira', 50),
            ],
        ];
    }
}
