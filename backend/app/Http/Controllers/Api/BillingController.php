<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Mail\PaymentReceipt;
use App\Models\Payment;
use App\Models\User;
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
        $data = $request->validate(['tier' => ['sometimes', \Illuminate\Validation\Rule::in(Plans::TIERS)]]);
        $tier = $data['tier'] ?? 'standard';
        $tiers = Plans::tiers();

        if (! Plans::enabled() || Plans::accountIsPro($event->user)) {
            return response()->json(['message' => 'This event is already Pro.'], 409);
        }
        // Moving up only: Small -> Pro -> Large, paying the difference.
        $current = $event->plan === 'pro' ? Plans::tierOf($event) : null;
        if ($current && Plans::tierRank($tier) <= Plans::tierRank($current)) {
            $name = Plans::tierInfo($current)['name'];
            return response()->json(['message' => $current === Plans::UNLIMITED
                ? "This event is already {$name}: there's nothing bigger to buy."
                : "This event is already {$name}. Choose a bigger size to upgrade."], 409);
        }
        $voters = $event->voters()->count();
        if ($voters > $tiers[$tier]['max_voters']) {
            return response()->json(['message' => "This event already has " . number_format($voters) . " voters, more than {$tiers[$tier]['name']} allows. Choose a bigger size."], 422);
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
            'kind' => Payment::KIND_UPGRADE,
            'tier' => $tier,
            'amount_kobo' => Plans::upgradePriceNaira($event, $tier) * 100,
            'currency' => config('ballotify.billing.currency', 'NGN'),
            'status' => Payment::PENDING,
        ]);

        $callback = rtrim(config('app.frontend_url'), '/') . "/dashboard/events/{$event->id}?tab=overview&payment=1";

        try {
            [$provider, $url] = Payments::checkout($request->user()->email, $request->user()->name, $payment->amount_kobo, $payment->reference, $callback, [
                'event_id' => $event->id,
                'payment_id' => $payment->id,
                'custom_fields' => [['display_name' => 'Event', 'variable_name' => 'event', 'value' => $event->name]],
            ], [], "{$tiers[$tier]['name']}: {$event->name}");
            $payment->update(['provider' => $provider]);
        } catch (\Throwable $e) {
            report($e);
            $payment->update(['status' => Payment::FAILED, 'failure_reason' => 'Could not start checkout']);
            return response()->json(['message' => 'We couldn’t reach the payment provider. Please try again in a moment.'], 502);
        }

        return response()->json(['authorization_url' => $url, 'reference' => $payment->reference]);
    }

    /**
     * Buy prepaid SMS credits for the account. They're used once an event's
     * included SMS (1 per voter of its Pro size) run out.
     */
    public function smsCheckout(Request $request)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:50', 'max:100000'],
            // Where to come back to (an event's Voters tab); otherwise the account page.
            'event_id' => ['nullable', 'integer'],
        ]);
        if (! Plans::enabled()) {
            return response()->json(['message' => 'SMS are not charged on this installation.'], 409);
        }
        if (! Payments::available()) {
            return response()->json(['message' => 'Online payment isn’t available yet. Please contact us to buy SMS credits.', 'payments_enabled' => false], 503);
        }
        $user = $request->user();
        $event = ! empty($data['event_id']) ? Event::accessibleBy($user)->find($data['event_id']) : null;
        $price = Plans::settings()['sms_price_naira'];

        $payment = Payment::create([
            'user_id' => $user->id,
            'event_id' => $event?->id,
            'event_name' => $event?->name,
            'reference' => 'SMS-' . Str::upper(Str::random(14)),
            'kind' => Payment::KIND_SMS,
            'sms_credits' => $data['quantity'],
            'amount_kobo' => $data['quantity'] * $price * 100,
            'currency' => config('ballotify.billing.currency', 'NGN'),
            'status' => Payment::PENDING,
        ]);
        $frontend = rtrim(config('app.frontend_url'), '/');
        $callback = $event ? "{$frontend}/dashboard/events/{$event->id}?tab=voters&payment=1" : "{$frontend}/dashboard/profile?payment=1";

        try {
            [$provider, $url] = Payments::checkout($user->email, $user->name, $payment->amount_kobo, $payment->reference, $callback, [
                'payment_id' => $payment->id,
                'custom_fields' => [['display_name' => 'SMS credits', 'variable_name' => 'sms', 'value' => (string) $data['quantity']]],
            ], [], number_format($data['quantity']) . ' SMS credits');
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
                Payment::SUCCESS => $payment->kind === Payment::KIND_SMS
                    ? 'Payment received. ' . number_format($payment->sms_credits) . ' SMS credits were added to your account.'
                    : 'Payment received. This event is now ' . (Plans::tiers()[$payment->tier ?? 'standard']['name'] ?? 'Pro') . '.',
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

                if ($payment->kind === Payment::KIND_SMS) {
                    // Prepaid SMS credits for the whole account.
                    User::whereKey($payment->user_id)->increment('sms_credits', $payment->sms_credits);
                    return $payment;
                }

                $event = $payment->event_id ? Event::find($payment->event_id) : null;
                if ($event) {
                    $tier = in_array($payment->tier, Plans::TIERS, true) ? $payment->tier : 'standard';
                    $size = Plans::tiers()[$tier];
                    // A late payment never shrinks an event the admin has since made bigger (e.g. Unlimited).
                    $keep = $event->plan === 'pro' && Plans::tierRank($event->pro_tier) > Plans::tierRank($tier);
                    $event->forceFill([
                        'plan' => 'pro', 'pro_tier' => $keep ? $event->pro_tier : $tier, 'upgraded_at' => now(), 'upgraded_via' => 'payment',
                        // 1 SMS per voter of the new size (a size upgrade tops the allowance up).
                        'sms_included' => max((int) $event->sms_included, $size['sms_included']),
                    ])->save();
                    Audit::log($event, 'event.upgraded', "Upgraded to {$size['name']} (payment {$payment->reference})");
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
                ->get(['reference', 'kind', 'tier', 'sms_credits', 'event_id', 'event_name', 'amount_kobo', 'currency', 'status', 'channel', 'paid_at', 'created_at'])
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
            'item' => $payment->kind === Payment::KIND_SMS
                ? number_format($payment->sms_credits) . ' SMS credits'
                : 'Ballotify ' . (Plans::tiers()[$payment->tier ?? 'standard']['name'] ?? 'Pro') . ' upgrade',
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
            // Pro sizes, smallest first, and the price of extra SMS.
            'tiers' => array_values(Plans::tiers()),
            'sms_price_naira' => $s['sms_price_naira'],
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
