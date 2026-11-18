<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Secret ballot: individual ballot rows are replaced by a running total per
 *    candidate, so no record exists of who voted for whom (not even timestamps
 *    or insert order that could be matched to when a code was used).
 * 2. Results visibility per event.
 * 3. Audit log of organiser actions.
 * 4. Two-step login columns; existing accounts are marked verified so they are
 *    not locked out by the new email-verification requirement.
 * 5. Drops the unused "expected voters" field.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- 1. Secret ballot -------------------------------------------------
        Schema::table('candidates', function (Blueprint $table) {
            $table->unsignedInteger('votes')->default(0)->after('image_path');
        });

        if (Schema::hasTable('votes')) {
            $totals = DB::table('votes')->select('candidate_id', DB::raw('COUNT(*) as c'))->groupBy('candidate_id')->pluck('c', 'candidate_id');
            foreach ($totals as $candidateId => $count) {
                DB::table('candidates')->where('id', $candidateId)->update(['votes' => $count]);
            }
            Schema::drop('votes');
        }

        // --- 2 & 5. Events -------------------------------------------------------
        Schema::table('events', function (Blueprint $table) {
            // live | after_close | private
            $table->string('results_visibility', 20)->default('after_close')->after('ends_at');
        });
        if (Schema::hasColumn('events', 'expected_voters')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('expected_voters');
            });
        }

        // --- 3. Audit log ----------------------------------------------------------
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

        // --- 4. Accounts -------------------------------------------------------------
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
        Schema::dropIfExists('audit_logs');
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('results_visibility');
            $table->unsignedInteger('expected_voters')->default(0);
        });
        // Individual ballots cannot be recreated from totals.
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('votes');
        });
    }
};
