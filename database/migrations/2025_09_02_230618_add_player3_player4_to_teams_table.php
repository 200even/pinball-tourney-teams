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
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('player3_id')->nullable()->after('player2_id')->constrained('players')->nullOnDelete();
            $table->foreignId('player4_id')->nullable()->after('player3_id')->constrained('players')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['player3_id']);
            $table->dropForeign(['player4_id']);
            $table->dropColumn(['player3_id', 'player4_id']);
        });
    }
};
