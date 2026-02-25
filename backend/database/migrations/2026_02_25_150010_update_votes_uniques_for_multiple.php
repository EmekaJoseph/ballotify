<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('voter_id');
            try {
                $table->dropUnique(['voter_id', 'category_id']);
            } catch (\Throwable $e) {
            }
            $table->unique(['voter_id', 'category_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            try {
                $table->dropUnique(['voter_id', 'category_id', 'candidate_id']);
            } catch (\Throwable $e) {
            }
            $table->unique(['voter_id', 'category_id']);
        });
    }
};
