<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ballotify voting schema.
 *
 *   users ─┬─< events >── event_types
 *          │     ├─< categories ─< candidates
 *          │     ├─< voters  (one-time access codes)
 *          │     └─< votes   (voter × category × candidate)
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
            $table->unsignedInteger('expected_voters')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
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
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->unique(['category_id', 'name']);
        });

        Schema::create('voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('code', 64);
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['event_id', 'code']);
        });

        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            // A voter can pick a given candidate at most once. "One per category"
            // for single-choice events is enforced in VoteController.
            $table->unique(['voter_id', 'category_id', 'candidate_id']);
            $table->index(['event_id', 'candidate_id']); // results tally
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
        Schema::dropIfExists('voters');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_types');
    }
};
