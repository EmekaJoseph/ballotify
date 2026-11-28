<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            // Two-step login (encrypted by the model casts).
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            // Platform owner (super admin) and account suspension.
            $table->boolean('is_admin')->default(false);
            $table->timestamp('suspended_at')->nullable();
            // Set by the platform admin: every event of this account is Pro, free of charge.
            $table->timestamp('pro_account_at')->nullable();
            // Size of the admin-granted account plan: small | standard | large | unlimited (null = large).
            $table->string('pro_account_tier', 10)->nullable();
            // Where money from paid public voting is sent.
            $table->string('payout_bank_name')->nullable();
            $table->string('payout_account_number', 20)->nullable();
            $table->string('payout_account_name')->nullable();
            // Paystack: bank code from their bank list, and the subaccount that
            // receives the organiser's share automatically (split payments).
            $table->string('payout_bank_code', 16)->nullable();
            // Prepaid SMS credits (account-wide), used once an event's included SMS run out.
            $table->unsignedInteger('sms_credits')->default(0);
            $table->string('paystack_subaccount_code', 64)->nullable();
            // First-run tour seen in the dashboard.
            $table->timestamp('onboarded_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Platform account log, shown to the platform admin: sign-ins (and failed ones),
        // account security changes, and every action an admin takes on an account.
        // Kept for a year (routes/console.php). Never holds passwords, codes or votes.
        Schema::create('account_logs', function (Blueprint $table) {
            $table->id();
            // Who did it (null = the system, or a server command).
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            // The account it concerns. The email is kept too, so entries stay readable after an account is deleted.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_email')->nullable();
            $table->string('action', 40);
            $table->string('description', 500);
            $table->string('ip', 45)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_logs');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
