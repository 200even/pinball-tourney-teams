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
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('matchplay_round_id');
            $table->integer('round_number');
            $table->string('name');
            $table->string('status'); // pending, active, completed
            $table->datetime('completed_at')->nullable();
            $table->json('matchplay_data')->nullable(); // Cache round data from API
            $table->timestamps();

            $table->unique(['tournament_id', 'matchplay_round_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rounds');
    }
};
