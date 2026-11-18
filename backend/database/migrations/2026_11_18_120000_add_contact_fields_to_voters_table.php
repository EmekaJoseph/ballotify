<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voter contact details, so codes can be delivered by email / SMS,
 * plus the outcome of the most recent invitation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->string('email')->nullable()->after('name');
            $table->string('phone', 32)->nullable()->after('email');
            $table->timestamp('invited_at')->nullable()->after('used_at');
            $table->string('invite_error', 500)->nullable()->after('invited_at');
            $table->index(['event_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'email']);
            $table->dropColumn(['email', 'phone', 'invited_at', 'invite_error']);
        });
    }
};
