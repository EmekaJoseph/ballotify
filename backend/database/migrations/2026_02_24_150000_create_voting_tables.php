<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ballotify voting schema.
 *
 *   users ─┬─< events
 *          │     ├─< categories ─< candidates  (votes = running total)
 *          │     ├─< voters          (one-time access codes; used_at = THAT they voted)
 *          │     ├─< ballot_receipts (proof a ballot was counted; not linked to a voter or a choice)
 *          │     ├─< vote_purchases  (paid public voting: Paystack payments for votes)
 *          │     ├─< payouts         (money paid out to the organiser for paid voting)
 *          │     ├─< event_members   (co-organisers and their roles)
 *          │     ├─< nominations     (public nominations before voting)
 *          │     ├─< result_certificates (signed snapshots of final results)
 *          │     └─< audit_logs      (organiser changes)
 *
 * Secret ballot (code voting): there is deliberately no table of individual
 * ballots, so no record exists of who voted for whom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('link_token')->unique();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            // live | after_close | private
            $table->string('results_visibility', 20)->default('after_close');
            // codes = secret ballot with one-time codes; paid = anyone buys votes (award nights).
            $table->string('voting_mode', 10)->default('codes');
            $table->unsignedInteger('vote_price_naira')->nullable();
            // Pro: the organiser's own look on the ballot and results pages.
            $table->string('brand_color', 7)->nullable();
            $table->string('brand_logo_path')->nullable();
            // Scheduled reminder to voters who haven't voted (Pro).
            $table->timestamp('reminder_at')->nullable();
            $table->string('reminder_channel', 8)->nullable(); // email | sms | both
            $table->timestamp('reminder_sent_at')->nullable();
            // Voters request their own code with an ID on the organiser's list (e.g. matric number).
            $table->boolean('self_registration')->default(false);
            $table->string('member_id_label', 40)->nullable();
            // Default language of the public voting pages (voters can switch): en | yo | ha | ig | fr
            $table->string('language', 5)->default('en');
            // Voting by USSD (Africa's Talking): voters dial in with this event number.
            $table->boolean('ussd_enabled')->default(false);
            $table->string('ussd_code', 8)->nullable()->unique();
            // Public nomination phase before voting (award nights).
            $table->boolean('nominations_enabled')->default(false);
            $table->timestamp('nominations_ends_at')->nullable();
            // Meeting rules (Pro, all off by default):
            //   weighted_votes  = each voter's ballot counts as their `weight` (shares, units...)
            //   proxies_enabled = a member can let another member vote for them (at most max_proxies each)
            //   quorum          = the result is only valid if enough voted: percent of members or a fixed number
            //                     (counted by weight when votes are weighted)
            $table->boolean('weighted_votes')->default(false);
            $table->boolean('proxies_enabled')->default(false);
            $table->unsignedTinyInteger('max_proxies')->default(2);
            $table->string('quorum_type', 8)->nullable(); // percent | count
            $table->unsignedInteger('quorum_value')->nullable();
            // Billing: 'free' or 'pro' (unlocked by a Paystack payment or by the admin).
            $table->string('plan', 10)->default('free');
            // Pro size: small | standard | large (null on Free).
            $table->string('pro_tier', 10)->nullable();
            // SMS included with this Pro event (1 per voter of its size) and how many were used.
            $table->unsignedInteger('sms_included')->default(0);
            $table->unsignedInteger('sms_used')->default(0);
            $table->timestamp('upgraded_at')->nullable();
            $table->string('upgraded_via', 10)->nullable(); // payment | admin
            $table->timestamps();
            $table->index(['reminder_at', 'reminder_sent_at']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Most picks allowed in this category: 1 = choose one, null = no limit.
            $table->unsignedSmallInteger('max_choices')->nullable()->default(1);
            // Show candidates in a random order on each ballot (no "first on the list" advantage).
            $table->boolean('shuffle_candidates')->default(false);
            // plurality = most votes wins (choose one, pick up to N, or approval when max_choices is null)
            // ranked    = voters rank candidates; counted in instant-runoff rounds
            // motion    = Yes / No / Abstain on a proposal; passes by pass_rule
            $table->string('method', 10)->default('plurality');
            // Motions: the full text, and simple | two_thirds | three_quarters (of Yes + No).
            $table->text('description')->nullable();
            $table->string('pass_rule', 16)->nullable();
            // Voter groups allowed to vote in this category (null = everyone).
            $table->json('groups')->nullable();
            // Ballots that answered this category (the base for "% of voters").
            $table->unsignedInteger('ballots')->default(0);
            $table->timestamps();
            $table->unique(['event_id', 'name']);
        });

        // Ranked-choice ballots, kept so a category can be counted in rounds. Nothing here
        // identifies a voter: no voter id, no time, and a random (v4) id so even the row
        // order doesn't follow the order people voted in.
        Schema::create('ranked_ballots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->json('ranking'); // candidate ids, first choice first
            // Weighted votes / proxies: how many votes this ranking carries.
            $table->unsignedInteger('weight')->default(1);
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Optional short blurb shown under the name.
            $table->string('description', 280)->nullable();
            $table->string('image_path')->nullable();
            // Private link for the candidate to edit their own photo and description.
            $table->string('profile_token', 64)->nullable()->unique();
            // Motions only: which fixed option this is (yes | no | abstain).
            $table->string('motion_choice', 8)->nullable();
            $table->unsignedInteger('votes')->default(0);
            $table->timestamps();
            $table->unique(['category_id', 'name']);
        });

        Schema::create('voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            // Eligibility ID for self-registration (matric number, member number...).
            $table->string('member_id', 64)->nullable();
            // Groups (faculty, level, branch...) deciding which categories this voter sees.
            $table->json('groups')->nullable();
            // Weighted votes: how many votes this voter's ballot counts as (1 unless set).
            $table->unsignedInteger('weight')->default(1);
            // Proxy: the member who votes for this voter (who then can't vote themselves).
            $table->foreignId('proxy_voter_id')->nullable()->constrained('voters')->nullOnDelete();
            $table->string('code', 64);
            $table->timestamp('used_at')->nullable()->index();
            // Outcome of the most recent email / SMS / WhatsApp invitation.
            $table->timestamp('invited_at')->nullable();
            $table->string('invited_via', 10)->nullable(); // email | sms | whatsapp
            $table->timestamp('reminded_at')->nullable();
            $table->string('invite_error', 500)->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'code']);
            $table->unique(['event_id', 'member_id']);
            $table->index(['event_id', 'email']);
        });

        // Each counted ballot gets a receipt code the voter can look up later.
        // Only a hash is stored, and nothing links it to a voter or a choice.
        Schema::create('ballot_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->char('code_hash', 64);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['event_id', 'code_hash']);
        });

        Schema::create('vote_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('candidate_name');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('amount_kobo');
            $table->unsignedBigInteger('fee_kobo')->default(0); // platform's share
            $table->string('currency', 3)->default('NGN');
            $table->string('reference', 64)->unique();
            $table->string('provider', 16)->default('paystack'); // paystack | flutterwave
            $table->string('email');
            $table->string('name')->nullable();
            // pending | success | failed | late (paid after voting closed: not counted, refund due)
            $table->string('status', 12)->default('pending');
            $table->string('channel', 32)->nullable();
            $table->string('failure_reason', 255)->nullable();
            // Set when Paystack split the payment straight to the organiser's subaccount.
            $table->string('subaccount', 64)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['event_id', 'status']);
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount_kobo');
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Co-organisers. A member is invited by email; user_id is set once that
        // person has a confirmed account with the same email.
        Schema::create('event_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 10); // editor | voters | viewer
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['event_id', 'email']);
            $table->index('email');
        });

        Schema::create('nominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('nominee_name', 120);
            // Lower-case, single-spaced: groups "Ada Obi" and "ada  obi" together.
            $table->string('normalized_name', 120);
            $table->string('reason', 280)->nullable();
            $table->string('nominator_email')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['event_id', 'category_id', 'normalized_name']);
        });

        // Signed snapshot of final results that anyone can verify later.
        Schema::create('result_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('serial', 24)->unique();
            $table->json('payload');
            $table->char('signature', 64);
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64);
            $table->string('description', 500);
            // True when the change happened after the first vote was cast.
            $table->boolean('during_voting')->default(false);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['event_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('result_certificates');
        Schema::dropIfExists('nominations');
        Schema::dropIfExists('event_members');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('vote_purchases');
        Schema::dropIfExists('ballot_receipts');
        Schema::dropIfExists('voters');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('ranked_ballots');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('events');
    }
};
