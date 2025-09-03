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

// Create test user for debugging
Route::get('/create-test-user', function () {
    try {
        $testEmail = 'test@example.com';
        $testPassword = 'password123';
        
        // Check if user already exists
        $existingUser = \App\Models\User::where('email', $testEmail)->first();
        if ($existingUser) {
            return response()->json([
                'message' => 'Test user already exists',
                'email' => $testEmail,
                'user_id' => $existingUser->id,
            ], 200, [], JSON_PRETTY_PRINT);
        }
        
        // Create test user
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => $testEmail,
            'password' => \Hash::make($testPassword),
            'email_verified_at' => now(),
        ]);
        
        return response()->json([
            'message' => 'Test user created successfully',
            'email' => $testEmail,
            'password' => $testPassword,
            'user_id' => $user->id,
        ], 200, [], JSON_PRETTY_PRINT);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500, [], JSON_PRETTY_PRINT);
    }
})->withoutMiddleware(['web']);

// Simple login test form
Route::get('/test-login-form', function () {
    return '<!DOCTYPE html>
<html>
<head><title>Login Test</title></head>
<body>
    <h1>Test Login Credentials</h1>
    <form action="/debug-login" method="POST">
        <div>
            <label>Email:</label><br>
            <input type="email" name="email" required style="width: 300px; padding: 5px;">
        </div><br>
        <div>
            <label>Password:</label><br>
            <input type="password" name="password" required style="width: 300px; padding: 5px;">
        </div><br>
        <button type="submit" style="padding: 10px 20px;">Test Login</button>
    </form>
    <br>
    <p><strong>Known users:</strong></p>
    <ul>
        <li>mike.brown475@gmail.com (Michael Brown)</li>
        <li>esfergus+1@gmail.com (Scott Ferguson)</li>
        <li>test@test.com (Foo Bar)</li>
    </ul>
</body>
</html>';
})->withoutMiddleware(['web']);

// Password reset form
Route::get('/reset-password-form', function () {
    return '<!DOCTYPE html>
<html>
<head><title>Reset Password</title></head>
<body>
    <h1>Reset User Password & Verify Email</h1>
    <form action="/reset-user-password" method="POST">
        <div>
            <label>Email:</label><br>
            <input type="email" name="email" required style="width: 300px; padding: 5px;" placeholder="user@example.com">
        </div><br>
        <div>
            <label>New Password:</label><br>
            <input type="text" name="new_password" required style="width: 300px; padding: 5px;" placeholder="newpassword123">
        </div><br>
        <button type="submit" style="padding: 10px 20px;">Reset Password & Verify Email</button>
    </form>
    <br>
    <p><strong>This will:</strong></p>
    <ul>
        <li>Set a new password for the user</li>
        <li>Mark their email as verified</li>
        <li>Allow them to log in immediately</li>
    </ul>
    <p><strong>Known users:</strong></p>
    <ul>
        <li>mike.brown475@gmail.com (Michael Brown)</li>
        <li>esfergus+1@gmail.com (Scott Ferguson)</li>
        <li>test@test.com (Foo Bar)</li>
    </ul>
</body>
</html>';
})->withoutMiddleware(['web']);

// Reset user password for debugging
Route::post('/reset-user-password', function (\Illuminate\Http\Request $request) {
    try {
        $email = $request->input('email');
        $newPassword = $request->input('new_password');
        
        if (!$email || !$newPassword) {
            return response()->json([
                'error' => 'Email and new_password are required'
            ], 400, [], JSON_PRETTY_PRINT);
        }
        
        $user = \App\Models\User::where('email', $email)->first();
        
        if (!$user) {
            return response()->json([
                'error' => 'User not found',
                'email' => $email
            ], 404, [], JSON_PRETTY_PRINT);
        }
        
        // Update password and verify email
        $user->update([
            'password' => \Hash::make($newPassword),
            'email_verified_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Password reset and email verified successfully',
            'user_id' => $user->id,
            'email' => $user->email,
            'new_password' => $newPassword,
        ], 200, [], JSON_PRETTY_PRINT);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500, [], JSON_PRETTY_PRINT);
    }
})->withoutMiddleware(['web']);

// Test Matchplay API endpoints
Route::get('/test-matchplay-api/{tournamentId}', function ($tournamentId) {
    try {
        // Use the first user with a Matchplay token for testing
        $user = \App\Models\User::whereNotNull('matchplay_api_token')->first();
        
        if (!$user) {
            return response()->json([
                'error' => 'No user with Matchplay API token found'
            ], 400, [], JSON_PRETTY_PRINT);
        }
        
        $matchplayService = new \App\Services\MatchplayApiService($user);
        
        // Test tournament info
        $tournamentData = $matchplayService->getTournament($tournamentId);
        
        // Test tournament players
        $playersData = $matchplayService->getTournamentPlayers($tournamentId);
        
        // Test tournament standings
        $standingsData = $matchplayService->getTournamentStandings($tournamentId);
        
        return response()->json([
            'tournament_id' => $tournamentId,
            'user_email' => $user->email,
            'tournament_info' => $tournamentData,
            'players_count' => count($playersData),
            'players_data' => $playersData,
            'standings_count' => count($standingsData),
            'standings_data' => $standingsData,
            'timestamp' => date('Y-m-d H:i:s'),
        ], 200, [], JSON_PRETTY_PRINT);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'tournament_id' => $tournamentId,
        ], 500, [], JSON_PRETTY_PRINT);
    }
})->withoutMiddleware(['web']);

// Debug local players database
Route::get('/debug-players', function () {
    try {
        $players = \App\Models\Player::latest()
            ->limit(50)
            ->get()
            ->map(function ($player) {
                return [
                    'id' => $player->id,
                    'matchplay_player_id' => $player->matchplay_player_id,
                    'name' => $player->name,
                    'created_at' => $player->created_at,
                ];
            });
            
        return response()->json([
            'total_players' => \App\Models\Player::count(),
            'recent_players' => $players,
            'timestamp' => date('Y-m-d H:i:s'),
        ], 200, [], JSON_PRETTY_PRINT);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s'),
        ], 500, [], JSON_PRETTY_PRINT);
    }
})->withoutMiddleware(['web']);

// Debug player sync discrepancy for tournament 206710
Route::get('/debug-player-sync/{tournamentId}', function ($tournamentId) {
    try {
        $user = \App\Models\User::whereNotNull('matchplay_api_token')->first();
        if (!$user) {
            return response()->json(['error' => 'No user with Matchplay API token found'], 404);
        }
        
        $matchplayService = new \App\Services\MatchplayApiService($user);
        $playersData = $matchplayService->getTournamentPlayers($tournamentId);
        
        // Get Matchplay player IDs
        $matchplayPlayerIds = collect($playersData)->pluck('playerId')->sort()->values();
        
        // Get existing players from database for these IDs
        $existingPlayers = \App\Models\Player::whereIn('matchplay_player_id', $matchplayPlayerIds)
            ->get()
            ->map(function ($player) {
                return [
                    'id' => $player->id,
                    'matchplay_player_id' => $player->matchplay_player_id,
                    'name' => $player->name,
                    'created_at' => $player->created_at,
                    'matchplay_data' => $player->matchplay_data,
                ];
            });
            
        // Get ALL players in database (to find extras)
        $allPlayers = \App\Models\Player::all()
            ->map(function ($player) {
                return [
                    'id' => $player->id,
                    'matchplay_player_id' => $player->matchplay_player_id,
                    'name' => $player->name,
                    'created_at' => $player->created_at,
                ];
            });
            
        $existingPlayerIds = $existingPlayers->pluck('matchplay_player_id')->sort()->values();
        
        // Find discrepancies
        $onlyInMatchplay = $matchplayPlayerIds->diff($existingPlayerIds);
        $onlyInDatabase = $existingPlayerIds->diff($matchplayPlayerIds);
        
        return response()->json([
            'tournament_id' => $tournamentId,
            'matchplay_players_count' => count($playersData),
            'database_players_count' => $existingPlayers->count(),
            'total_database_players' => $allPlayers->count(),
            'matchplay_player_ids' => $matchplayPlayerIds,
            'database_player_ids' => $existingPlayerIds,
            'only_in_matchplay' => $onlyInMatchplay,
            'only_in_database' => $onlyInDatabase,
            'existing_players' => $existingPlayers,
            'all_database_players' => $allPlayers,
            'timestamp' => date('Y-m-d H:i:s'),
        ], 200, [], JSON_PRETTY_PRINT);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'tournament_id' => $tournamentId,
        ], 500, [], JSON_PRETTY_PRINT);
    }
})->withoutMiddleware(['web']);

// Debug tournament player IDs field
Route::get('/debug-tournament-players/{tournamentId}', function ($tournamentId) {
    try {
        $tournaments = \App\Models\Tournament::where('matchplay_tournament_id', $tournamentId)->get();
        
        $result = [];
        foreach ($tournaments as $tournament) {
            $storedPlayerIds = $tournament->tournament_player_ids ?? [];
            $playersInDb = \App\Models\Player::whereIn('matchplay_player_id', $storedPlayerIds)->get();
            
            $result[] = [
                'tournament_id' => $tournament->id,
                'user_id' => $tournament->user_id,
                'user_email' => $tournament->user->email ?? 'unknown',
                'matchplay_tournament_id' => $tournament->matchplay_tournament_id,
                'name' => $tournament->name,
                'stored_player_ids_count' => count($storedPlayerIds),
                'stored_player_ids' => $storedPlayerIds,
                'players_found_in_db' => $playersInDb->count(),
                'players_with_names' => $playersInDb->map(function($p) {
                    return [
                        'matchplay_player_id' => $p->matchplay_player_id,
                        'name' => $p->name,
                        'has_real_name' => !str_starts_with($p->name, 'Player '),
                    ];
                }),
                'created_at' => $tournament->created_at,
            ];
        }
        
        return response()->json([
            'matchplay_tournament_id' => $tournamentId,
            'tournaments_found' => count($tournaments),
            'tournaments' => $result,
            'timestamp' => date('Y-m-d H:i:s'),
        ], 200, [], JSON_PRETTY_PRINT);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'tournament_id' => $tournamentId,
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

// Simple login test page
Route::get('/test-login', function () {
    return '
<!DOCTYPE html>
<html>
<head>
    <title>Login Test</title>
    <meta name="csrf-token" content="' . csrf_token() . '">
</head>
<body>
    <h2>Login Test</h2>
    <form method="POST" action="/login">
        <input type="hidden" name="_token" value="' . csrf_token() . '">
        <div>
            <label>Email:</label><br>
            <input type="email" name="email" value="debug@test.com" required>
        </div>
        <div>
            <label>Password:</label><br>
            <input type="password" name="password" value="password123" required>
        </div>
        <div>
            <input type="checkbox" name="remember" value="1">
            <label>Remember me</label>
        </div>
        <div>
            <button type="submit">Login</button>
        </div>
    </form>
    
    <h3>Test Credentials:</h3>
    <p>Email: debug@test.com</p>
    <p>Password: password123</p>
    
    <h3>Available Users:</h3>
    <ul>' . 
    collect(\App\Models\User::all())->map(function($user) {
        return '<li>' . $user->email . ' (ID: ' . $user->id . ', Created: ' . $user->created_at . ')</li>';
    })->join('') . 
    '</ul>
</body>
</html>';
});
