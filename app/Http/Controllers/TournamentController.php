<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Round;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\MatchplayApiService;
use App\Services\PlayerNameMatchingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TournamentController extends Controller
{
    public function index()
    {
        $tournaments = auth()->user()->tournaments()
            ->withCount('teams')
            ->with(['teams.player1', 'teams.player2'])
            ->latest()
            ->get()
            ->map(function ($tournament) {
                return [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'status' => $tournament->status,
                    'matchplay_tournament_id' => $tournament->matchplay_tournament_id,
                    'start_date' => $tournament->start_date,
                    'end_date' => $tournament->end_date,
                    'qr_code_uuid' => $tournament->qr_code_uuid,
                    'teams_count' => $tournament->teams_count,
                    'created_at' => $tournament->created_at,
                ];
            });

        return Inertia::render('Tournaments/TournamentList', [
            'tournaments' => $tournaments,
        ]);
    }

    public function create()
    {
        if (! auth()->user()->hasMatchplayToken()) {
            return redirect()->route('profile.edit')
                ->with('error', 'Please add your Matchplay API token first.');
        }

        return Inertia::render('Tournaments/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'matchplay_tournament_id' => 'required|string',
            'team_size' => 'required|integer|in:2,4',
        ]);

        try {
            \Log::info('Tournament creation started', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email ?? 'N/A',
                'user_name' => auth()->user()->name ?? 'N/A',
                'matchplay_tournament_id' => $request->matchplay_tournament_id,
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $matchplayService = new MatchplayApiService(auth()->user());

            // Verify tournament exists and get data
            $tournamentData = $matchplayService->getTournament($request->matchplay_tournament_id);
            if (! $tournamentData) {
                throw ValidationException::withMessages([
                    'matchplay_tournament_id' => 'Tournament not found in Matchplay API.',
                ]);
            }

            \Log::info('Tournament data retrieved from Matchplay API');

            // Handle nested data structure from Matchplay API
            $data = $tournamentData['data'] ?? $tournamentData;

            $currentUserId = auth()->id();
            \Log::info('About to create tournament', [
                'user_id_for_tournament' => $currentUserId,
                'authenticated_user_email' => auth()->user()->email,
                'tournament_name' => $data['name'] ?? 'Unnamed Tournament',
            ]);

            $tournament = Tournament::create([
                'user_id' => $currentUserId,
                'matchplay_tournament_id' => $request->matchplay_tournament_id,
                'name' => $data['name'] ?? 'Unnamed Tournament',
                'description' => $data['description'] ?? null,
                'start_date' => isset($data['startUtc']) ? now()->parse($data['startUtc']) : null,
                'end_date' => isset($data['endUtc']) ? now()->parse($data['endUtc']) : null,
                'status' => $data['status'] ?? 'active',
                'team_size' => $request->team_size,
                'matchplay_data' => $tournamentData,
            ]);

            \Log::info('Tournament created successfully', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
            ]);

            // Import players and create rounds
            try {
                $this->importTournamentData($tournament);
                \Log::info('Tournament data import completed successfully');
            } catch (\Exception $importException) {
                \Log::error('Tournament data import failed', [
                    'tournament_id' => $tournament->id,
                    'error' => $importException->getMessage(),
                    'trace' => $importException->getTraceAsString(),
                ]);
                // Don't fail the entire creation if import fails
            }

            \Log::info('Redirecting to tournament show page', [
                'tournament_id' => $tournament->id,
            ]);

            return redirect()->route('tournaments.show', $tournament)
                ->with('success', 'Tournament created successfully!');

        } catch (\Exception $e) {
            \Log::error('Tournament creation failed', [
                'user_id' => auth()->id(),
                'matchplay_tournament_id' => $request->matchplay_tournament_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Failed to create tournament: '.$e->getMessage()]);
        }
    }

    public function show(Tournament $tournament)
    {
        $this->authorize('view', $tournament);

        $tournament->load([
            'teams.player1',
            'teams.player2',
            'teams.roundScores.round',
            'rounds',
        ]);

        // Get players from this specific tournament using stored player IDs
        if (! empty($tournament->tournament_player_ids)) {
            $availablePlayers = Player::whereIn('matchplay_player_id', $tournament->tournament_player_ids)
                ->orderBy('name')
                ->get();
        } else {
            // Fallback: try to sync tournament data first, then get players
            try {
                $this->importTournamentData($tournament);
                $tournament->refresh();

                if (! empty($tournament->tournament_player_ids)) {
                    $availablePlayers = Player::whereIn('matchplay_player_id', $tournament->tournament_player_ids)
                        ->orderBy('name')
                        ->get();
                } else {
                    $availablePlayers = collect();
                }
            } catch (\Exception $e) {
                $availablePlayers = collect();
            }
        }

        // Calculate current standings
        $standings = $tournament->calculateStandings();

        return Inertia::render('Tournaments/Show', [
            'tournament' => $tournament,
            'standings' => $standings,
            'qrCodeUrl' => $tournament->qr_code_url,
            'availablePlayers' => $availablePlayers,
        ]);
    }

    public function sync(Tournament $tournament)
    {
        $this->authorize('update', $tournament);

        try {
            $this->importTournamentData($tournament);

            return back()->with('success', 'Tournament data synced successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to sync tournament: '.$e->getMessage()]);
        }
    }

    public function toggleAutoSync(Tournament $tournament)
    {
        $this->authorize('update', $tournament);

        $tournament->update([
            'auto_sync' => ! $tournament->auto_sync,
        ]);

        $status = $tournament->auto_sync ? 'enabled' : 'disabled';

        return back()->with('success', "Auto-sync {$status} for this tournament.");
    }

    public function updatePlayerNames(Request $request, Tournament $tournament)
    {
        $this->authorize('update', $tournament);

        $validated = $request->validate([
            'players' => 'required|array',
            'players.*.id' => 'required|exists:players,id',
            'players.*.name' => 'required|string|max:255',
        ]);

        try {
            foreach ($validated['players'] as $playerData) {
                $player = Player::find($playerData['id']);
                if ($player) {
                    $player->update(['name' => $playerData['name']]);
                }
            }

            return back()->with('success', 'Player names updated successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update player names: '.$e->getMessage()]);
        }
    }

    /**
     * Match player names using historical IFPA tournament data
     */
    public function matchNamesFromIfpaTournament(Tournament $tournament, Request $request)
    {
        $this->authorize('update', $tournament);

        $request->validate([
            'ifpa_tournament_id' => 'required|integer|min:1',
        ]);

        if (! $tournament->user->matchplay_api_token) {
            return back()->withErrors(['error' => 'Matchplay API token is required.']);
        }

        try {
            $matchplayService = new MatchplayApiService($tournament->user);
            $nameMatchingService = new PlayerNameMatchingService;

            // Get current tournament players
            $playersData = $matchplayService->getTournamentPlayers($tournament->matchplay_tournament_id);

            // Use the user's IFPA API key
            $ifpaApiKey = $tournament->user->ifpa_api_key;

            if (! $ifpaApiKey) {
                return back()->withErrors(['error' => 'IFPA API key is required to match names from IFPA tournaments.']);
            }

            // Match names using the IFPA tournament
            $matchedPlayers = $nameMatchingService->matchPlayersFromIfpaTournament(
                $playersData,
                $request->ifpa_tournament_id,
                $ifpaApiKey
            );

            // Update players with matched names
            $updatedCount = 0;
            foreach ($matchedPlayers as $playerData) {
                if (isset($playerData['name_source']) && $playerData['name_source'] === 'ifpa_tournament') {
                    Player::where('matchplay_player_id', $playerData['playerId'])
                        ->update(['name' => $playerData['name']]);
                    $updatedCount++;
                }
            }

            if ($updatedCount > 0) {
                return back()->with('success', "Successfully matched {$updatedCount} player names from IFPA tournament {$request->ifpa_tournament_id}.");
            } else {
                return back()->with('info', 'No additional player names could be matched from this IFPA tournament.');
            }

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to match names from IFPA tournament: '.$e->getMessage()]);
        }
    }

    /**
     * Show the tournament name matching interface
     */
    public function nameMatching()
    {
        return Inertia::render('settings/name-matching');
    }

    /**
     * Process tournament name matching request
     */
    public function processNameMatching(Request $request)
    {
        $request->validate([
            'tournament_pairs' => 'required|array|min:1',
            'tournament_pairs.*.matchplay_tournament_id' => 'required|string',
            'tournament_pairs.*.ifpa_tournament_id' => 'required|integer|min:1',
        ]);

        $user = auth()->user();

        if (! $user->hasMatchplayToken()) {
            return back()->withErrors(['error' => 'Matchplay API token is required. Please add it in your profile settings.']);
        }

        if (! $user->hasIfpaApiKey()) {
            return back()->withErrors(['error' => 'IFPA API key is required. Please add it in your profile settings.']);
        }

        try {
            $matchplayService = new MatchplayApiService($user);
            $nameMatchingService = new PlayerNameMatchingService;

            $totalUpdatedCount = 0;
            $processedPairs = [];

            // Process each tournament pair using conservative matching
            foreach ($request->tournament_pairs as $pair) {
                $matchplayId = $pair['matchplay_tournament_id'];
                $ifpaId = $pair['ifpa_tournament_id'];

                try {
                    // Get Matchplay tournament players
                    $playersData = $matchplayService->getTournamentPlayers($matchplayId);

                    if (empty($playersData)) {
                        $processedPairs[] = "Matchplay {$matchplayId}: No players found";

                        continue;
                    }

                    // Use conservative single-tournament matching (no multi-tournament for 1:1 pairs)
                    $matchedPlayers = $nameMatchingService->matchPlayersFromIfpaTournament(
                        $playersData,
                        $ifpaId,
                        $user->ifpa_api_key
                    );

                    // Check for existing real names that would be overwritten
                    $conflicts = [];
                    foreach ($matchedPlayers as $playerData) {
                        if (isset($playerData['name_source']) && $playerData['name_source'] === 'ifpa_tournament') {
                            $existingPlayer = Player::where('matchplay_player_id', $playerData['playerId'])->first();
                            if ($existingPlayer &&
                                ! str_starts_with($existingPlayer->name, 'Player ') &&
                                $existingPlayer->name !== $playerData['name']) {
                                $conflicts[] = [
                                    'player_id' => $playerData['playerId'],
                                    'current_name' => $existingPlayer->name,
                                    'new_name' => $playerData['name'],
                                ];
                            }
                        }
                    }

                    // If there are conflicts, abort the entire operation
                    if (! empty($conflicts)) {
                        $conflictDetails = collect($conflicts)->map(function ($conflict) {
                            return "Player {$conflict['player_id']}: '{$conflict['current_name']}' → '{$conflict['new_name']}'";
                        })->implode('; ');

                        throw new \Exception("Name matching aborted to prevent overwriting existing real names. Conflicts: {$conflictDetails}");
                    }

                    // Update or create players with matched names (only if no conflicts)
                    $pairUpdatedCount = 0;
                    foreach ($matchedPlayers as $playerData) {
                        if (isset($playerData['name_source']) && $playerData['name_source'] === 'ifpa_tournament') {
                            Player::updateOrCreate(
                                ['matchplay_player_id' => $playerData['playerId']],
                                [
                                    'name' => $playerData['name'],
                                    'matchplay_data' => [
                                        'profile' => $playerData['profile'] ?? null,
                                        'standing' => $playerData['standing'] ?? null,
                                    ],
                                ]
                            );
                            $pairUpdatedCount++;
                        } else {
                            // Also create players that weren't matched but exist in the tournament
                            Player::updateOrCreate(
                                ['matchplay_player_id' => $playerData['playerId']],
                                [
                                    'name' => $playerData['name'] ?? "Player {$playerData['playerId']}",
                                    'matchplay_data' => [
                                        'profile' => $playerData['profile'] ?? null,
                                        'standing' => $playerData['standing'] ?? null,
                                    ],
                                ]
                            );
                        }
                    }

                    $totalUpdatedCount += $pairUpdatedCount;
                    $processedPairs[] = "Matchplay {$matchplayId} ↔ IFPA {$ifpaId}: {$pairUpdatedCount} matches";

                } catch (\Exception $e) {
                    $processedPairs[] = "Matchplay {$matchplayId} ↔ IFPA {$ifpaId}: Failed ({$e->getMessage()})";
                }
            }

            if ($totalUpdatedCount > 0) {
                $summary = implode('; ', $processedPairs);

                return back()->with('success', "Successfully matched {$totalUpdatedCount} total player names using conservative 1:1 matching. Details: {$summary}");
            } else {
                $summary = implode('; ', $processedPairs);

                return back()->with('info', "No player names could be matched. This may be due to tied positions being excluded for accuracy. Details: {$summary}");
            }

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to match names: '.$e->getMessage()]);
        }
    }

    private function importTournamentData(Tournament $tournament): void
    {
        $matchplayService = new MatchplayApiService($tournament->user);
        $nameMatchingService = new PlayerNameMatchingService;

        // Import players from standings
        $playersData = $matchplayService->getTournamentPlayers($tournament->matchplay_tournament_id);

        // Try to match player names from existing database records first
        $playersData = $nameMatchingService->matchPlayersFromDatabase($playersData);

        // Store tournament player IDs for scoping
        $tournamentPlayerIds = collect($playersData)->pluck('playerId')->filter()->toArray();
        $tournament->update(['tournament_player_ids' => $tournamentPlayerIds]);

        foreach ($playersData as $playerData) {
            $playerName = $playerData['name']; // May have been improved by name matching

            Player::updateOrCreate(
                ['matchplay_player_id' => $playerData['playerId']],
                [
                    'name' => $playerName,
                    'matchplay_data' => [
                        'profile' => $playerData['profile'],
                        'standing' => $playerData['standing'],
                    ],
                ]
            );
        }

        // Import rounds
        $roundsData = $matchplayService->getTournamentRounds($tournament->matchplay_tournament_id);
        foreach ($roundsData as $roundData) {
            Round::updateOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'matchplay_round_id' => $roundData['roundId'],
                ],
                [
                    'round_number' => $roundData['index'] + 1,
                    'name' => $roundData['name'],
                    'status' => $roundData['status'],
                    'completed_at' => $roundData['completedAt'] ? now()->parse($roundData['completedAt']) : null,
                    'matchplay_data' => $roundData,
                ]
            );
        }

        // Update team scores if we have completed rounds
        $this->updateTeamScores($tournament);
    }

    /**
     * Manually import additional players by their Matchplay IDs
     */
    public function importAdditionalPlayers(Tournament $tournament, Request $request)
    {
        $this->authorize('update', $tournament);

        $request->validate([
            'player_ids' => 'required|array',
            'player_ids.*' => 'required|integer|min:1',
        ]);

        try {
            $matchplayService = new MatchplayApiService($tournament->user);
            $imported = 0;

            foreach ($request->player_ids as $playerId) {
                try {
                    // Get player profile from Matchplay API
                    $profile = $matchplayService->getPlayer($playerId);

                    if ($profile) {
                        $playerName = $profile['name'] ?? "Player {$playerId}";

                        Player::updateOrCreate(
                            ['matchplay_player_id' => $playerId],
                            [
                                'name' => $playerName,
                                'matchplay_data' => [
                                    'profile' => $profile,
                                    'standing' => null, // No standing data for manually added players
                                ],
                            ]
                        );

                        $imported++;
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to import additional player', [
                        'player_id' => $playerId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return back()->with('success', "Successfully imported {$imported} additional players.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to import additional players: '.$e->getMessage()]);
        }
    }

    private function updateTeamScores(Tournament $tournament): void
    {
        $matchplayService = new MatchplayApiService($tournament->user);
        $standings = $matchplayService->getTournamentStandings($tournament->matchplay_tournament_id);

        // Update round-by-round scores for all teams
        $this->syncTeamRoundScores($tournament, $matchplayService);

        foreach ($tournament->teams as $team) {
            $player1Standing = collect($standings)->firstWhere('playerId', $team->player1->matchplay_player_id);
            $player2Standing = collect($standings)->firstWhere('playerId', $team->player2->matchplay_player_id);

            if ($player1Standing && $player2Standing) {
                $totalPoints = ($player1Standing['points'] ?? 0) + ($player2Standing['points'] ?? 0);
                $gamesPlayed = ($player1Standing['gamesPlayed'] ?? 0) + ($player2Standing['gamesPlayed'] ?? 0);

                $team->update([
                    'total_points' => $totalPoints,
                    'games_played' => $gamesPlayed,
                ]);

                // Also update total points from round scores for accuracy
                $team->updateTotalPoints();
            }
        }
    }

    /**
     * Sync round-by-round scores for all teams from Matchplay API
     */
    private function syncTeamRoundScores(Tournament $tournament, MatchplayApiService $matchplayService): void
    {
        try {
            // Get all completed rounds for this tournament
            $completedRounds = $tournament->rounds()->where('status', 'completed')->get();

            if ($completedRounds->isEmpty()) {
                return;
            }

            foreach ($tournament->teams as $team) {
                // Get round-by-round data for all players on the team
                $playerRoundScores = [];
                $playerScoresByRound = [];

                foreach ($team->players() as $index => $player) {
                    if ($player) {
                        $playerRoundScores[$index] = $matchplayService->getPlayerRoundScores(
                            $tournament->matchplay_tournament_id,
                            (int) $player->matchplay_player_id
                        );
                        $playerScoresByRound[$index] = collect($playerRoundScores[$index])->keyBy('roundNumber');
                    }
                }

                // Update team round scores for each completed round
                foreach ($completedRounds as $round) {
                    $roundData = [];
                    $gamesData = [];

                    // Collect data for each player (only for players that exist on the team)
                    $playerCount = $team->players()->count();
                    for ($i = 0; $i < $playerCount; $i++) {
                        $playerRoundData = $playerScoresByRound[$i]?->get($round->round_number, []) ?? [];
                        $roundData['player'.($i + 1).'_points'] = $playerRoundData['points'] ?? 0;
                        $roundData['player'.($i + 1).'_games_played'] = $playerRoundData['gamesPlayed'] ?? 0;
                        $gamesData['player'.($i + 1).'_games'] = $playerRoundData['games'] ?? [];
                    }

                    // Ensure all 4 player fields exist (set to 0 for missing players)
                    for ($i = $playerCount; $i < 4; $i++) {
                        $roundData['player'.($i + 1).'_points'] = 0;
                        $roundData['player'.($i + 1).'_games_played'] = 0;
                        $gamesData['player'.($i + 1).'_games'] = [];
                    }

                    // Create or update the team round score record
                    \App\Models\TeamRoundScore::updateOrCreate(
                        [
                            'team_id' => $team->id,
                            'round_id' => $round->id,
                        ],
                        array_merge($roundData, [
                            'games_data' => $gamesData,
                        ])
                    );
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the entire update process
            \Illuminate\Support\Facades\Log::warning('Failed to sync team round scores: '.$e->getMessage());
        }
    }
}
