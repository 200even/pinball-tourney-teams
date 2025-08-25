<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    try {
        return Inertia::render('welcome');
    } catch (Exception $e) {
        // Fallback if Inertia/frontend assets fail
        return response()->view('welcome-fallback', [
            'error' => $e->getMessage()
        ]);
    }
})->name('home');

// Simple HTML fallback for root
Route::get('/simple', function () {
    return '<!DOCTYPE html>
<html>
<head><title>Pinball Tournament Tracker</title></head>
<body>
    <h1>🎯 Pinball Tournament Tracker</h1>
    <p>Laravel is running successfully!</p>
    <p>Time: ' . date('Y-m-d H:i:s') . '</p>
    <p>Environment: ' . env('APP_ENV') . '</p>
    <ul>
        <li><a href="/up">Health Check</a></li>
        <li><a href="/status">Status Info</a></li>
        <li><a href="/hello">Hello Test</a></li>
        <li><a href="/test">Laravel Test</a></li>
        <li><a href="/debug">Debug Info</a></li>
        <li><a href="/simple-test.php">PHP Test</a></li>
    </ul>
</body>
</html>';
});

// Health check is handled by Laravel's built-in health route in bootstrap/app.php

// Simple test endpoint
Route::get('/test', function () {
    return response('Laravel is working! ' . date('Y-m-d H:i:s'));
});

// Even simpler test
Route::get('/hello', function () {
    return 'Hello World!';
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

// Simple debug without database
Route::get('/status', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'app_env' => env('APP_ENV'),
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
    ], 200, [], JSON_PRETTY_PRINT);
});

// Route without sessions middleware
Route::get('/no-session', function () {
    return response()->json([
        'message' => 'This route bypasses sessions',
        'timestamp' => date('Y-m-d H:i:s'),
        'session_driver' => config('session.driver'),
        'db_connection' => config('database.default'),
        'session_env' => env('SESSION_DRIVER'),
    ]);
})->withoutMiddleware(['web']);

// Config check route
Route::get('/config-check', function () {
    return response()->json([
        'session_driver_config' => config('session.driver'),
        'session_driver_env' => env('SESSION_DRIVER'),
        'session_driver_default' => env('SESSION_DRIVER', 'file'),
        'config_cached' => app()->configurationIsCached(),
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
})->withoutMiddleware(['web']);

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
