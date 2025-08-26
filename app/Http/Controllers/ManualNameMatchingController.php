<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Services\MatchplayApiService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManualNameMatchingController extends Controller
{
    public function index()
    {
        return Inertia::render('settings/manual-name-matching');
    }

    public function loadTournament(Request $request)
    {
        \Log::info('Manual name matching loadTournament called', [
            'tournament_id' => $request->get('matchplay_tournament_id'),
            'user_id' => auth()->id(),
        ]);

        $request->validate([
            'matchplay_tournament_id' => 'required|string',
        ]);

        $user = auth()->user();

        if (!$user->hasMatchplayToken()) {
            return back()->withErrors(['error' => 'Matchplay API token is required. Please add it in your profile settings.']);
        }

        try {
            \Log::info('Creating MatchplayApiService...');
            $matchplayService = new MatchplayApiService($user);
            
            \Log::info('Getting tournament info...');
            // Get tournament info
            $tournamentData = $matchplayService->getTournament($request->matchplay_tournament_id);
            \Log::info('Tournament data received', ['data' => $tournamentData ? 'found' : 'null']);
            
            if (!$tournamentData) {
                \Log::warning('Tournament not found', ['tournament_id' => $request->matchplay_tournament_id]);
                return back()->withErrors(['error' => "Tournament {$request->matchplay_tournament_id} not found on Matchplay API."]);
            }

            \Log::info('Getting tournament players...');
            // Get tournament standings with final positions
            $playersData = $matchplayService->getTournamentPlayers($request->matchplay_tournament_id);
            \Log::info('Players data received', ['count' => count($playersData)]);
            
            if (empty($playersData)) {
                \Log::warning('No players found', ['tournament_id' => $request->matchplay_tournament_id]);
                return back()->withErrors(['error' => "Tournament {$request->matchplay_tournament_id} found but no player standings available. This tournament may not be completed or may not have standings yet."]);
            }

            \Log::info('Sorting players data...');
            // Sort by final position
            usort($playersData, function($a, $b) {
                if (isset($a['position']) && isset($b['position'])) {
                    return $a['position'] <=> $b['position'];
                }
                // Fallback to points if no position
                return ($b['points'] ?? 0) <=> ($a['points'] ?? 0);
            });
            \Log::info('Players sorted successfully');

            \Log::info('Getting existing players from database...');
            // Get existing names from database if they exist
            $existingPlayers = Player::whereIn('matchplay_player_id', collect($playersData)->pluck('playerId'))
                ->pluck('name', 'matchplay_player_id')
                ->toArray();
            \Log::info('Existing players retrieved', ['count' => count($existingPlayers)]);

            // Prepare data for frontend
            $rankedPlayers = collect($playersData)->map(function($player, $index) use ($existingPlayers) {
                $playerId = $player['playerId'];
                $currentName = $existingPlayers[$playerId] ?? "Player {$playerId}";
                
                return [
                    'matchplay_player_id' => $playerId,
                    'position' => $player['position'] ?? ($index + 1),
                    'points' => $player['points'] ?? 0,
                    'current_name' => $currentName,
                    'has_real_name' => !str_starts_with($currentName, 'Player '),
                ];
            })->values();
            
            \Log::info('Prepared ranked players', ['count' => count($rankedPlayers)]);
            \Log::info('Returning response with tournament data...');

            // Return Inertia response with redirect instruction
            return Inertia::render('settings/manual-name-matching', [
                'tournament_data' => [
                    'id' => $request->matchplay_tournament_id,
                    'name' => $tournamentData['name'] ?? 'Unknown Tournament',
                    'status' => $tournamentData['status'] ?? 'unknown',
                ],
                'ranked_players' => $rankedPlayers,
                'should_redirect' => true,
            ]);

        } catch (\Exception $e) {
            \Log::error('Manual name matching failed', [
                'tournament_id' => $request->matchplay_tournament_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors(['error' => 'Failed to load tournament: ' . $e->getMessage()]);
        }
    }

    public function saveNames(Request $request)
    {
        $request->validate([
            'matchplay_tournament_id' => 'required|string',
            'player_names' => 'required|array',
            'player_names.*' => 'nullable|string|max:255',
        ]);

        try {
            $updatedCount = 0;
            
            foreach ($request->player_names as $matchplayPlayerId => $name) {
                // Only update if the name is different and not empty
                if (!empty(trim($name))) {
                    Player::updateOrCreate(
                        ['matchplay_player_id' => $matchplayPlayerId],
                        [
                            'name' => trim($name),
                            'matchplay_data' => [
                                'tournament_id' => $request->matchplay_tournament_id,
                                'manually_updated' => true,
                                'updated_at' => now()->toISOString(),
                            ],
                        ]
                    );
                    $updatedCount++;
                }
            }

            return back()->with('success', "Successfully updated {$updatedCount} player names.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to save names: ' . $e->getMessage()]);
        }
    }
}