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
        Schema::table('tournaments', function (Blueprint $table) {
            // Remove unique constraint to allow multiple users to create tournaments
            // for the same Matchplay tournament ID
            $table->dropUnique(['matchplay_tournament_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            // Restore unique constraint
            $table->unique('matchplay_tournament_id');
        });
    }
};
