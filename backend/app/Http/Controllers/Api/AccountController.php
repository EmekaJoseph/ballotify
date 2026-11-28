<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AccountLogger;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\VotePurchase;
use App\Support\Paystack;
use App\Support\Plans;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Payout bank account (Paystack split payments) and the organiser's data rights. */
class AccountController extends Controller
{
    // ------------------------------------------------------------------ payout account

    public function banks()
    {
        if (! Paystack::configured()) {
            return response()->json(['banks' => [], 'verified' => false]);
        }
        try {
            return response()->json(['banks' => Paystack::banks(), 'verified' => true]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['banks' => [], 'verified' => false]);
        }
    }

    /** Look up the account holder's name before saving, so a typo is caught. */
    public function resolve(Request $request)
    {
        $data = $request->validate([
            'bank_code' => ['required', 'string', 'max:16'],
            'account_number' => ['required', 'digits:10'],
        ], ['account_number.digits' => 'Enter the 10-digit account number (NUBAN).']);
        abort_unless(Paystack::configured(), 503, 'Bank verification isn’t available right now.');

        $name = Paystack::resolveAccount($data['account_number'], $data['bank_code']);
        if (! $name) {
            throw ValidationException::withMessages(['account_number' => ['We couldn’t find this account at that bank. Check the number and bank.']]);
        }
        return response()->json(['account_name' => $name]);
    }

    /**
     * Save the payout account. With Paystack set up, the account is verified and
     * a subaccount is created, so future paid votes pay the organiser automatically.
     */
    public function savePayoutAccount(Request $request)
    {
        $data = $request->validate([
            'bank_code' => ['required', 'string', 'max:16'],
            'account_number' => ['required', 'digits:10'],
        ], ['account_number.digits' => 'Enter the 10-digit account number (NUBAN).']);
        $user = $request->user();
        abort_unless(Paystack::configured(), 503, 'Bank verification isn’t available right now.');

        $bank = collect(Paystack::banks())->firstWhere('code', $data['bank_code']);
        if (! $bank) {
            throw ValidationException::withMessages(['bank_code' => ['Choose your bank from the list.']]);
        }
        $name = Paystack::resolveAccount($data['account_number'], $data['bank_code']);
        if (! $name) {
            throw ValidationException::withMessages(['account_number' => ['We couldn’t find this account at that bank. Check the number and bank.']]);
        }

        try {
            $code = Paystack::upsertSubaccount(
                $user->paystack_subaccount_code,
                $name,
                $data['bank_code'],
                $data['account_number'],
                (float) Plans::settings()['paid_vote_fee_percent'],
            );
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Paystack couldn’t set up automatic payouts right now. Please try again shortly.'], 502);
        }

        $user->forceFill([
            'payout_bank_name' => $bank['name'],
            'payout_bank_code' => $data['bank_code'],
            'payout_account_number' => $data['account_number'],
            'payout_account_name' => $name,
            'paystack_subaccount_code' => $code,
        ])->save();

        return response()->json($user);
    }

    // ------------------------------------------------------------------ data rights (NDPR / GDPR)

    /** Everything we hold about the organiser, as JSON. Ballot contents don't exist, so they can't be included. */
    public function export(Request $request)
    {
        $user = $request->user();
        $events = Event::where('user_id', $user->id)->with(['categories', 'candidates'])->withCount('voters')->get();

        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'account' => $user->only(['name', 'email', 'email_verified_at', 'created_at', 'two_factor_enabled',
                'payout_bank_name', 'payout_account_number', 'payout_account_name']),
            'events' => $events->map(fn ($e) => $e->only(['name', 'description', 'starts_at', 'ends_at', 'voting_mode',
                'results_visibility', 'plan', 'created_at', 'voters_count']) + [
                'categories' => $e->categories->pluck('name'),
                'candidates' => $e->candidates->map->only(['name', 'description']),
            ]),
            'payments' => Payment::where('user_id', $user->id)->get(['reference', 'event_name', 'amount_kobo', 'status', 'paid_at']),
            'payouts' => Payout::where('user_id', $user->id)->get(['amount_kobo', 'note', 'created_at']),
        ])->header('Content-Disposition', 'attachment; filename="ballotify-my-data.json"');
    }

    /**
     * Delete the account and everything in it. Refused while paid-voting money
     * is still owed to the organiser, so nobody loses a payout by accident.
     */
    public function destroy(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password:sanctum']]);
        $user = $request->user();
        if ($user->is_admin) {
            throw ValidationException::withMessages(['password' => ['Platform admin accounts can’t be deleted here.']]);
        }

        $owed = 0;
        foreach (Event::where('user_id', $user->id)->where('voting_mode', Event::MODE_PAID)->get() as $event) {
            $manual = $event->purchases()->where('status', VotePurchase::SUCCESS)->whereNull('subaccount');
            $owed += (int) (clone $manual)->sum('amount_kobo') - (int) (clone $manual)->sum('fee_kobo') - (int) $event->payouts()->sum('amount_kobo');
        }
        if ($owed > 0) {
            throw ValidationException::withMessages(['password' => ['You still have ₦' . number_format($owed / 100, 2) . ' of paid-voting money to be paid out. Contact us so we can settle it before you delete the account.']]);
        }

        // Uploaded files aren't covered by the database cascade: remove them first.
        foreach (Event::where('user_id', $user->id)->with('candidates:id,event_id,image_path')->get(['id', 'brand_logo_path']) as $event) {
            foreach ($event->candidates as $candidate) {
                CandidateController::deleteImage($candidate->image_path);
            }
            $logo = $event->brand_logo_path;
            if ($logo && str_starts_with($logo, 'branding/') && is_file(public_path($logo))) {
                @unlink(public_path($logo));
            }
        }

        $user->tokens()->delete();
        AccountLogger::record('account.deleted', "Account deleted by its owner ({$user->name})", $user, $user);
        $user->delete(); // events, voters, ballot totals and team links go with it (cascade)

        return response()->json(['message' => 'Your account has been deleted.']);
    }
}
