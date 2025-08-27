<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // Redirect to appropriate page based on authentication
    if (auth()->check()) {
        return redirect()->route('dashboard');
    } else {
        return redirect()->route('login');
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

// Database environment check
Route::get('/db-env', function () {
    return response()->json([
        'DATABASE_URL' => env('DATABASE_URL') ? 'SET (length: ' . strlen(env('DATABASE_URL')) . ')' : 'NOT SET',
        'DB_CONNECTION' => env('DB_CONNECTION'),
        'DB_HOST' => env('DB_HOST'),
        'DB_PORT' => env('DB_PORT'),
        'DB_DATABASE' => env('DB_DATABASE'),
        'DB_USERNAME' => env('DB_USERNAME'),
        'DB_PASSWORD' => env('DB_PASSWORD') ? 'SET' : 'NOT SET',
        'config_db_host' => config('database.connections.pgsql.host'),
        'config_db_database' => config('database.connections.pgsql.database'),
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
})->withoutMiddleware(['web']);

// Debug tournament creation
Route::get('/debug-tournaments', function () {
    try {
        $tournaments = \App\Models\Tournament::with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($tournament) {
                return [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'matchplay_tournament_id' => $tournament->matchplay_tournament_id,
                    'user_id' => $tournament->user_id,
                    'user_email' => $tournament->user->email ?? 'N/A',
                    'created_at' => $tournament->created_at,
                ];
            });
            
        return response()->json([
            'total_tournaments' => \App\Models\Tournament::count(),
            'recent_tournaments' => $tournaments,
            'timestamp' => date('Y-m-d H:i:s'),
        ], 200, [], JSON_PRETTY_PRINT);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s'),
        ], 500, [], JSON_PRETTY_PRINT);
    }
})->withoutMiddleware(['web']);

// Test tournament creation without auth
Route::get('/test-tournament-creation', function () {
    try {
        // Check if we can access the database
        $userCount = \App\Models\User::count();
        $tournamentCount = \App\Models\Tournament::count();
        
        // Check if users have required API tokens
        $usersWithTokens = \App\Models\User::whereNotNull('matchplay_api_token')->count();
        
        return response()->json([
            'database_accessible' => true,
            'total_users' => $userCount,
            'total_tournaments' => $tournamentCount,
            'users_with_matchplay_tokens' => $usersWithTokens,
            'recent_users' => \App\Models\User::latest()->limit(3)->pluck('email'),
            'timestamp' => date('Y-m-d H:i:s'),
        ], 200, [], JSON_PRETTY_PRINT);
    } catch (\Exception $e) {
        return response()->json([
            'database_accessible' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s'),
        ], 500, [], JSON_PRETTY_PRINT);
    }
})->withoutMiddleware(['web']);

// Debug user accounts
Route::get('/debug-users', function () {
    try {
        $users = \App\Models\User::latest()
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'has_matchplay_token' => !empty($user->matchplay_api_token),
                    'tournaments_count' => $user->tournaments()->count(),
                    'created_at' => $user->created_at,
                    'email_verified_at' => $user->email_verified_at,
                    'password_set' => !empty($user->password),
                ];
            });
            
        return response()->json([
            'total_users' => \App\Models\User::count(),
            'users' => $users,
            'timestamp' => date('Y-m-d H:i:s'),
        ], 200, [], JSON_PRETTY_PRINT);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s'),
        ], 500, [], JSON_PRETTY_PRINT);
    }
})->withoutMiddleware(['web']);

// Debug login attempt
Route::post('/debug-login', function (\Illuminate\Http\Request $request) {
    try {
        $email = $request->input('email');
        $password = $request->input('password');
        
        // Check if user exists
        $user = \App\Models\User::where('email', $email)->first();
        
        if (!$user) {
            return response()->json([
                'user_exists' => false,
                'email_searched' => $email,
                'total_users' => \App\Models\User::count(),
                'all_emails' => \App\Models\User::pluck('email')->toArray(),
            ], 200, [], JSON_PRETTY_PRINT);
        }
        
        // Check password
        $passwordMatches = \Hash::check($password, $user->password);
        
        return response()->json([
            'user_exists' => true,
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'password_matches' => $passwordMatches,
            'email_verified' => !is_null($user->email_verified_at),
            'created_at' => $user->created_at,
        ], 200, [], JSON_PRETTY_PRINT);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500, [], JSON_PRETTY_PRINT);
    }
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
