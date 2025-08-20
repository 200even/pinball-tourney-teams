<?php

use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;

// Public leaderboard routes (no auth required)
Route::get('/leaderboard/{qrCodeUuid}', [LeaderboardController::class, 'public'])
    ->name('tournaments.leaderboard.public');
Route::get('/leaderboard/{qrCodeUuid}/refresh', [LeaderboardController::class, 'refresh'])
    ->name('tournaments.leaderboard.refresh');

// Authenticated tournament routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('tournaments', TournamentController::class);
    Route::post('tournaments/{tournament}/sync', [TournamentController::class, 'sync'])
        ->name('tournaments.sync');
    
    // Team management routes
    Route::resource('tournaments.teams', TeamController::class)
        ->except(['show', 'create', 'edit']);
    Route::post('tournaments/{tournament}/teams/{team}/regenerate-name', [TeamController::class, 'regenerateName'])
        ->name('tournaments.teams.regenerate-name');
});
