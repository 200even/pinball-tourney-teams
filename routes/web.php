<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Health check endpoint for Railway
Route::get('/up', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'pinball-tournament-tracker'
    ]);
});

// Simple test endpoint
Route::get('/test', function () {
    return response('Laravel is working! ' . date('Y-m-d H:i:s'));
});

// Debug endpoint for Railway deployment
Route::get('/debug', function () {
    $info = [
        'status' => 'Laravel is running',
        'timestamp' => now()->toISOString(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'environment' => [
            'APP_ENV' => env('APP_ENV'),
            'APP_DEBUG' => env('APP_DEBUG'),
            'DB_CONNECTION' => env('DB_CONNECTION'),
            'APP_KEY_SET' => env('APP_KEY') ? 'Yes' : 'No',
        ],
        'database' => 'testing...'
    ];
    
    try {
        DB::connection()->getPdo();
        $info['database'] = 'Connected successfully';
    } catch (Exception $e) {
        $info['database'] = 'Failed: ' . $e->getMessage();
    }
    
    return response()->json($info, 200, [], JSON_PRETTY_PRINT);
});

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
