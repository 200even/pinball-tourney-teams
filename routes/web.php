<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = auth()->user();
        
        $tournaments = $user->tournaments()
            ->withCount('teams')
            ->latest()
            ->limit(3)
            ->get()
            ->map(function ($tournament) {
                return [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'status' => $tournament->status,
                    'matchplay_tournament_id' => $tournament->matchplay_tournament_id,
                    'teams_count' => $tournament->teams_count,
                    'qr_code_uuid' => $tournament->qr_code_uuid,
                    'created_at' => $tournament->created_at,
                ];
            });

        $totalTournaments = $user->tournaments()->count();
        $activeTournaments = $user->tournaments()->where('status', 'active')->count();
        $totalTeams = \App\Models\Team::whereHas('tournament', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();

        return Inertia::render('dashboard', [
            'recentTournaments' => $tournaments,
            'totalTournaments' => $totalTournaments,
            'activeTournaments' => $activeTournaments,
            'totalTeams' => $totalTeams,
        ]);
    })->name('dashboard');
});

require __DIR__.'/tournaments.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
