<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voting schema.
 *
 *   users ─┬─< events
 *          │     ├─< categories ─< candidates  (votes = running total)
 *          │     ├─< voters          (one-time access codes; used_at = THAT they voted)
 *          │     ├─< ballot_receipts (proof a ballot was counted; not linked to a voter or a choice)
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
            // The organisation's logo on the voting, nominations and results pages.
            $table->string('brand_logo_path')->nullable();
            // Scheduled email reminder to voters who haven't voted.
            $table->timestamp('reminder_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            // Public nomination phase before voting.
            $table->boolean('nominations_enabled')->default(false);
            $table->timestamp('nominations_ends_at')->nullable();
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
            // Nominations: names the organiser suggests; the public picks one or types their own.
            $table->json('nominee_suggestions')->nullable();
            // Ballots that answered this category (the base for "% of voters").
            $table->unsignedInteger('ballots')->default(0);
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
            // The organiser's own ID for this voter (matric number, member number...), optional.
            $table->string('member_id', 64)->nullable();
            $table->string('code', 64);
            $table->timestamp('used_at')->nullable()->index();
            // Outcome of the most recent email or WhatsApp invitation.
            $table->timestamp('invited_at')->nullable();
            $table->string('invited_via', 10)->nullable(); // email | whatsapp
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
            $table->char('ip_hash', 64)->nullable();
            // Hash of an anonymous ID the browser keeps: with ip_hash, "the same person".
            $table->char('device_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['event_id', 'category_id', 'normalized_name']);
            $table->index(['category_id', 'ip_hash', 'device_hash']);
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
        Schema::dropIfExists('ballot_receipts');
        Schema::dropIfExists('voters');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('events');
    }
};
