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
            // Billing: 'free' or 'pro' (unlocked by a Paystack payment or by the admin).
            $table->string('plan', 10)->default('free');
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
            $table->timestamps();
            $table->unique(['event_id', 'name']);
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Optional short blurb shown under the name.
            $table->string('description', 280)->nullable();
            $table->string('image_path')->nullable();
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
            $table->string('code', 64);
            $table->timestamp('used_at')->nullable()->index();
            // Outcome of the most recent email / SMS / WhatsApp invitation.
            $table->timestamp('invited_at')->nullable();
            $table->string('invited_via', 10)->nullable(); // email | sms | whatsapp
            $table->timestamp('reminded_at')->nullable();
            $table->string('invite_error', 500)->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'code']);
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
            $table->string('email');
            $table->string('name')->nullable();
            // pending | success | failed | late (paid after voting closed: not counted, refund due)
            $table->string('status', 12)->default('pending');
            $table->string('channel', 32)->nullable();
            $table->string('failure_reason', 255)->nullable();
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
        Schema::dropIfExists('event_members');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('vote_purchases');
        Schema::dropIfExists('ballot_receipts');
        Schema::dropIfExists('voters');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('events');
    }
};
