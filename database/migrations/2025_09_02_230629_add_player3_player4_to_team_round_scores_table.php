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
        Schema::table('team_round_scores', function (Blueprint $table) {
            $table->decimal('player3_points', 8, 2)->default(0)->after('player2_points');
            $table->decimal('player4_points', 8, 2)->default(0)->after('player3_points');
            $table->integer('player3_games_played')->default(0)->after('player2_games_played');
            $table->integer('player4_games_played')->default(0)->after('player3_games_played');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_round_scores', function (Blueprint $table) {
            $table->dropColumn(['player3_points', 'player4_points', 'player3_games_played', 'player4_games_played']);
        });
    }
};
