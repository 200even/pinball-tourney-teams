<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlayerNameMatchingService
{
    /**
     * Try to match Matchplay players with known names from historical tournament data
     */
    public function matchPlayersFromIfpaTournament(array $matchplayPlayers, int $ifpaTournamentId, ?string $ifpaApiKey = null): array
    {
        if (!$ifpaApiKey) {
            Log::info('No IFPA API key provided for name matching');
            return $matchplayPlayers;
        }

        try {
            // Get IFPA tournament standings
            $ifpaStandings = $this->getIfpaTournamentStandings($ifpaTournamentId, $ifpaApiKey);
            
            if (empty($ifpaStandings)) {
                Log::warning('No IFPA tournament standings found', ['tournament_id' => $ifpaTournamentId]);
                return $matchplayPlayers;
            }

            // Sort IFPA standings by position
            usort($ifpaStandings, function($a, $b) {
                return (int)$a['position'] <=> (int)$b['position'];
            });

            // Find tied positions to exclude them (conservative approach)
            $positionCounts = [];
            foreach ($ifpaStandings as $standing) {
                $pos = (int)$standing['position'];
                $positionCounts[$pos] = ($positionCounts[$pos] ?? 0) + 1;
            }

            // Create mapping using only non-tied positions
            // For finals tournaments, we need to map based on relative position within the subset
            $ifpaPositionNames = [];
            $sequentialPosition = 1;
            foreach ($ifpaStandings as $standing) {
                if (!isset($standing['name'])) continue;
                
                $officialPosition = (int)$standing['position'];
                
                // Skip tied positions - conservative approach
                if ($positionCounts[$officialPosition] > 1) {
                    Log::info('Skipping tied position in single tournament', [
                        'tournament_id' => $ifpaTournamentId,
                        'position' => $officialPosition,
                        'name' => $standing['name'],
                        'tied_count' => $positionCounts[$officialPosition]
                    ]);
                    continue;
                }
                
                // This is a clear, non-tied position - map it sequentially
                $ifpaPositionNames[$sequentialPosition] = [
                    'name' => $standing['name'],
                    'original_position' => $officialPosition
                ];
                $sequentialPosition++;
            }

            Log::info('Found IFPA player names by position', [
                'tournament_id' => $ifpaTournamentId,
                'player_count' => count($ifpaPositionNames)
            ]);

            // Sort Matchplay players by their position/points to match standings
            usort($matchplayPlayers, function($a, $b) {
                // Sort by position (ascending) or by points (descending) if position not available
                if (isset($a['position']) && isset($b['position'])) {
                    return $a['position'] <=> $b['position'];
                }
                return ($b['points'] ?? 0) <=> ($a['points'] ?? 0);
            });

                        // Match by tournament position, ensuring no duplicate names
            $matchedCount = 0;
            $usedNames = [];

            foreach ($matchplayPlayers as $index => &$matchplayPlayer) {
                $matchplayPosition = $index + 1; // 1-based position

                if (isset($ifpaPositionNames[$matchplayPosition])) {
                    $ifpaData = $ifpaPositionNames[$matchplayPosition];
                    $potentialName = $ifpaData['name'];
                    $originalPosition = $ifpaData['original_position'];

                    // Only assign the name if it hasn't been used already
                    if (!in_array($potentialName, $usedNames)) {
                        $matchplayPlayer['name'] = $potentialName;
                        $matchplayPlayer['name_source'] = 'ifpa_tournament';
                        $usedNames[] = $potentialName;
                        $matchedCount++;

                        Log::info('Matched player by relative position', [
                            'matchplay_position' => $matchplayPosition,
                            'ifpa_original_position' => $originalPosition,
                            'matchplay_id' => $matchplayPlayer['playerId'],
                            'old_name' => $matchplayPlayer['name'] ?? 'Unknown',
                            'new_name' => $potentialName
                        ]);
                    } else {
                        Log::info('Skipped duplicate name assignment', [
                            'matchplay_position' => $matchplayPosition,
                            'matchplay_id' => $matchplayPlayer['playerId'],
                            'duplicate_name' => $potentialName
                        ]);
                    }
                }
            }

            Log::info('Successfully matched players from IFPA tournament', [
                'tournament_id' => $ifpaTournamentId,
                'matched_players' => $matchedCount,
                'total_players' => count($matchplayPlayers)
            ]);

            return $matchplayPlayers;

        } catch (\Exception $e) {
            Log::error('Failed to match players from IFPA tournament', [
                'tournament_id' => $ifpaTournamentId,
                'error' => $e->getMessage()
            ]);
            
            return $matchplayPlayers;
        }
    }

    /**
     * Match players using multiple IFPA tournaments with intelligent cross-referencing
     */
    public function matchPlayersFromMultipleIfpaTournaments(array $matchplayPlayers, array $ifpaTournamentIds, ?string $ifpaApiKey = null): array
    {
        if (!$ifpaApiKey) {
            Log::info('No IFPA API key provided for name matching');
            return $matchplayPlayers;
        }

        if (empty($ifpaTournamentIds)) {
            Log::info('No IFPA tournament IDs provided');
            return $matchplayPlayers;
        }

        // Step 1: Collect all IFPA tournament data
        $allIfpaData = [];
        foreach ($ifpaTournamentIds as $tournamentId) {
            try {
                $standings = $this->getIfpaTournamentStandings($tournamentId, $ifpaApiKey);
                if (!empty($standings)) {
                    $allIfpaData[$tournamentId] = $standings;
                    Log::info('Loaded IFPA tournament data', [
                        'tournament_id' => $tournamentId,
                        'player_count' => count($standings)
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to load IFPA tournament', [
                    'tournament_id' => $tournamentId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (empty($allIfpaData)) {
            Log::warning('No IFPA tournament data could be loaded');
            return $matchplayPlayers;
        }

        // Step 2: Build comprehensive player database from all tournaments
        $playerDatabase = $this->buildPlayerDatabase($allIfpaData);
        
        // Step 3: Sort Matchplay players by performance
        usort($matchplayPlayers, function($a, $b) {
            if (isset($a['position']) && isset($b['position'])) {
                return $a['position'] <=> $b['position'];
            }
            return ($b['points'] ?? 0) <=> ($a['points'] ?? 0);
        });

        // Step 4: Intelligent matching using cross-tournament data
        $matchedPlayers = $this->performIntelligentMatching($matchplayPlayers, $playerDatabase);

        Log::info('Multi-tournament intelligent matching completed', [
            'tournament_ids' => $ifpaTournamentIds,
            'tournaments_loaded' => count($allIfpaData),
            'unique_players_found' => count($playerDatabase),
            'total_matched' => count(array_filter($matchedPlayers, fn($p) => isset($p['name_source'])))
        ]);

        return $matchedPlayers;
    }

    /**
     * Build a conservative player database using only non-tied positions from multiple tournaments
     */
    private function buildPlayerDatabase(array $allIfpaData): array
    {
        $combinedPositionToName = []; // Final mapping of position => name
        
        foreach ($allIfpaData as $tournamentId => $standings) {
            // Sort standings by position
            usort($standings, function($a, $b) {
                return (int)$a['position'] <=> (int)$b['position'];
            });

            // Find tied positions to exclude them
            $positionCounts = [];
            foreach ($standings as $standing) {
                $pos = (int)$standing['position'];
                $positionCounts[$pos] = ($positionCounts[$pos] ?? 0) + 1;
            }
            
            // Extract non-tied positions from this tournament
            $tournamentPositionToName = [];
            $sequentialPosition = 1;
            foreach ($standings as $standing) {
                if (!isset($standing['name'])) continue;
                
                $officialPosition = (int)$standing['position'];
                
                // Skip tied positions - we only want unambiguous ranks
                if ($positionCounts[$officialPosition] > 1) {
                    Log::info('Skipping tied position', [
                        'tournament_id' => $tournamentId,
                        'position' => $officialPosition,
                        'name' => $standing['name'],
                        'tied_count' => $positionCounts[$officialPosition]
                    ]);
                    continue;
                }
                
                // This is a clear, non-tied position
                $tournamentPositionToName[$sequentialPosition] = [
                    'name' => $standing['name'],
                    'original_position' => $officialPosition
                ];
                $sequentialPosition++;
            }
            
            // Fill gaps in combined data using this tournament's data
            foreach ($tournamentPositionToName as $position => $data) {
                // Only add if we don't already have a name for this position
                if (!isset($combinedPositionToName[$position])) {
                    $combinedPositionToName[$position] = $data;
                }
            }
            
            Log::info('Processed tournament for conservative matching', [
                'tournament_id' => $tournamentId,
                'total_players' => count($standings),
                'non_tied_positions' => count($tournamentPositionToName),
                'tied_positions_skipped' => count($standings) - count($tournamentPositionToName),
                'new_positions_added' => count(array_diff_key($tournamentPositionToName, $combinedPositionToName))
            ]);
        }

        Log::info('Built conservative player database', [
            'total_tournaments' => count($allIfpaData),
            'total_clear_positions' => count($combinedPositionToName)
        ]);

        return $combinedPositionToName;
    }

    /**
     * Perform conservative matching using only clear, non-tied positions
     */
    private function performIntelligentMatching(array $matchplayPlayers, array $positionToName): array
    {
        $usedNames = [];
        $matchedCount = 0;

        foreach ($matchplayPlayers as $index => &$matchplayPlayer) {
            $matchplayPosition = $index + 1;
            
                        // Only assign if we have a clear name for this exact position and it hasn't been used
            if (isset($positionToName[$matchplayPosition])) {
                $candidateData = $positionToName[$matchplayPosition];
                $candidateName = $candidateData['name'];
                $originalPosition = $candidateData['original_position'];

                // Only assign if this name hasn't been used yet (no duplicates)
                if (!in_array($candidateName, $usedNames)) {
                    $matchplayPlayer['name'] = $candidateName;
                    $matchplayPlayer['name_source'] = 'ifpa_tournament';
                    $usedNames[] = $candidateName;
                    $matchedCount++;

                    Log::info('Conservative match found', [
                        'matchplay_position' => $matchplayPosition,
                        'ifpa_original_position' => $originalPosition,
                        'matchplay_id' => $matchplayPlayer['playerId'],
                        'matched_name' => $candidateName
                    ]);
                } else {
                    Log::info('Skipped duplicate name', [
                        'matchplay_position' => $matchplayPosition,
                        'matchplay_id' => $matchplayPlayer['playerId'],
                        'duplicate_name' => $candidateName
                    ]);
                }
            }
        }

        Log::info('Conservative matching results', [
            'total_matched' => $matchedCount,
            'match_rate' => round(($matchedCount / count($matchplayPlayers)) * 100, 1) . '%'
        ]);

        return $matchplayPlayers;
    }

    /**
     * Get tournament standings from IFPA API
     */
    private function getIfpaTournamentStandings(int $tournamentId, string $apiKey): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get("https://api.ifpapinball.com/tournament/{$tournamentId}/results", [
            'api_key' => $apiKey
        ]);

        if (!$response->successful()) {
            Log::warning('IFPA tournament API request failed', [
                'tournament_id' => $tournamentId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return [];
        }

        $data = $response->json();
        
        // IFPA tournament results API returns results directly
        if (isset($data['results'])) {
            return $data['results'];
        }

        Log::warning('Unexpected IFPA tournament API response structure', [
            'tournament_id' => $tournamentId,
            'response_keys' => array_keys($data)
        ]);

        return [];
    }

    /**
     * Try to match players using existing database records
     */
    public function matchPlayersFromDatabase(array $matchplayPlayers): array
    {
        $matchedCount = 0;
        
        foreach ($matchplayPlayers as &$matchplayPlayer) {
            $playerId = $matchplayPlayer['playerId'];
            
            // Look for existing player record with a real name
            $existingPlayer = Player::where('matchplay_player_id', $playerId)
                ->where('name', 'not like', 'Player %')
                ->first();
            
            if ($existingPlayer) {
                $matchplayPlayer['name'] = $existingPlayer->name;
                $matchplayPlayer['name_source'] = 'database';
                $matchedCount++;
            }
        }

        if ($matchedCount > 0) {
            Log::info('Matched players from database', [
                'matched_players' => $matchedCount,
                'total_players' => count($matchplayPlayers)
            ]);
        }

        return $matchplayPlayers;
    }
}
