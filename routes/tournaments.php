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
    Route::post('tournaments/{tournament}/toggle-auto-sync', [TournamentController::class, 'toggleAutoSync'])
        ->name('tournaments.toggle-auto-sync');
    Route::post('tournaments/{tournament}/update-player-names', [TournamentController::class, 'updatePlayerNames'])
        ->name('tournaments.update-player-names');
    
    Route::post('tournaments/{tournament}/match-names-from-ifpa', [TournamentController::class, 'matchNamesFromIfpaTournament'])
        ->name('tournaments.match-names-from-ifpa');
    


    
    Route::post('tournaments/{tournament}/import-additional-players', [TournamentController::class, 'importAdditionalPlayers'])
        ->name('tournaments.import-additional-players');
    
    // Team management routes
    Route::resource('tournaments.teams', TeamController::class)
        ->except(['show', 'create', 'edit']);
    Route::post('tournaments/{tournament}/teams/{team}/regenerate-name', [TeamController::class, 'regenerateName'])
        ->name('tournaments.teams.regenerate-name');
});
