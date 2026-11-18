<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ballotify voting schema.
 *
 *   users ─┬─< events >── event_types
 *          │     ├─< categories ─< candidates  (votes = running total)
 *          │     ├─< voters      (one-time access codes; used_at = THAT they voted)
 *          │     └─< audit_logs  (organiser changes)
 *
 * Secret ballot: there is deliberately no table of individual ballots, so no
 * record exists of who voted for whom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_types', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique();   // 'single' | 'multiple'
            $table->string('name');
            $table->timestamps();
        });

        // Reference data the app cannot work without, so it ships with the schema.
        $now = now();
        DB::table('event_types')->insert([
            ['key' => 'single', 'name' => 'Single Choice', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'multiple', 'name' => 'Multiple Choice', 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_type_id')->nullable()->constrained('event_types')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('link_token')->unique();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            // live | after_close | private
            $table->string('results_visibility', 20)->default('after_close');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
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
            // Outcome of the most recent email / SMS invitation.
            $table->timestamp('invited_at')->nullable();
            $table->string('invite_error', 500)->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'code']);
            $table->index(['event_id', 'email']);
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
        Schema::dropIfExists('voters');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_types');
    }
};
