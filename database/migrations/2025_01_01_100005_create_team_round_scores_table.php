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
        Schema::create('team_round_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->decimal('player1_points', 8, 2)->default(0);
            $table->decimal('player2_points', 8, 2)->default(0);
            $table->decimal('total_points', 8, 2)->default(0);
            $table->integer('player1_games_played')->default(0);
            $table->integer('player2_games_played')->default(0);
            $table->json('games_data')->nullable(); // Individual game results
            $table->timestamps();

            $table->unique(['team_id', 'round_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_round_scores');
    }
};
