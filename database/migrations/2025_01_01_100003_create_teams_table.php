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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player1_id')->constrained('players');
            $table->foreignId('player2_id')->constrained('players');
            $table->string('name'); // Custom team name
            $table->string('generated_name'); // Original funny generated name
            $table->decimal('total_points', 8, 2)->default(0);
            $table->integer('games_played')->default(0);
            $table->integer('position')->nullable();
            $table->timestamps();

            // Ensure players can't be on multiple teams in same tournament
            $table->unique(['tournament_id', 'player1_id']);
            $table->unique(['tournament_id', 'player2_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
