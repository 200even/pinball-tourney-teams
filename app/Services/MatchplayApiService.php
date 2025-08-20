<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MatchplayApiService
{
    private const BASE_URL = 'https://app.matchplay.events/api';

    public function __construct(private User $user)
    {
        if (!$user->hasMatchplayToken()) {
            throw new \InvalidArgumentException('User must have a Matchplay API token');
        }
    }

    /**
     * Get tournament details from Matchplay API
     */
    public function getTournament(string $tournamentId): ?array
    {
        $response = $this->makeRequest("tournaments/{$tournamentId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Get tournament standings from Matchplay API
     */
    public function getTournamentStandings(string $tournamentId): array
    {
        $response = $this->makeRequest("tournaments/{$tournamentId}/standings");

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }

    /**
     * Get tournament rounds from Matchplay API
     */
    public function getTournamentRounds(string $tournamentId): array
    {
        $response = $this->makeRequest("tournaments/{$tournamentId}/rounds");

        if ($response->successful()) {
            $data = $response->json();
            // The API returns data in a 'data' key according to the docs
            return $data['data'] ?? [];
        }

        return [];
    }

    /**
     * Get games in a tournament from Matchplay API
     */
    public function getTournamentGames(string $tournamentId): array
    {
        $response = $this->makeRequest("tournaments/{$tournamentId}/games");

        if ($response->successful()) {
            $data = $response->json();
            return $data['data'] ?? [];
        }

        return [];
    }

    /**
     * Get individual game details
     */
    public function getGame(string $tournamentId, string $gameId): ?array
    {
        $response = $this->makeRequest("tournaments/{$tournamentId}/games/{$gameId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Get player profile
     */
    public function getPlayer(int $playerId): ?array
    {
        $response = $this->makeRequest("profiles/{$playerId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Get user's dashboard to find their tournaments
     */
    public function getDashboard(): array
    {
        $response = $this->makeRequest('dashboard');

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }

    /**
     * Get tournament list
     */
    public function getTournaments(array $filters = []): array
    {
        $response = $this->makeRequest('tournaments', $filters);

        if ($response->successful()) {
            $data = $response->json();
            return $data['data'] ?? [];
        }

        return [];
    }

    /**
     * Get series list
     */
    public function getSeries(): array
    {
        $response = $this->makeRequest('series');

        if ($response->successful()) {
            $data = $response->json();
            return $data['data'] ?? [];
        }

        return [];
    }

    /**
     * Search for tournaments, players, etc.
     */
    public function search(string $query): array
    {
        $response = $this->makeRequest('search', ['q' => $query]);

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }

    /**
     * Get player statistics for a tournament
     */
    public function getPlayerStats(string $tournamentId, int $playerId): ?array
    {
        $response = $this->makeRequest("tournaments/{$tournamentId}/players/{$playerId}/games");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Extract players from tournament standings
     */
    public function getTournamentPlayers(string $tournamentId): array
    {
        $standings = $this->getTournamentStandings($tournamentId);
        $players = [];

        foreach ($standings as $standing) {
            if (isset($standing['playerId'])) {
                // Get player profile for more details
                $playerProfile = $this->getPlayer($standing['playerId']);
                
                if ($playerProfile) {
                    $players[] = [
                        'playerId' => $standing['playerId'],
                        'name' => $playerProfile['name'] ?? 'Unknown Player',
                        'ifpaId' => $playerProfile['ifpaId'] ?? null,
                        'points' => $standing['points'] ?? 0,
                        'gamesPlayed' => $standing['gamesPlayed'] ?? 0,
                        'position' => $standing['position'] ?? null,
                        'profile' => $playerProfile,
                        'standing' => $standing,
                    ];
                } else {
                    // Fallback if profile fetch fails
                    $players[] = [
                        'playerId' => $standing['playerId'],
                        'name' => 'Player ' . $standing['playerId'],
                        'ifpaId' => null,
                        'points' => $standing['points'] ?? 0,
                        'gamesPlayed' => $standing['gamesPlayed'] ?? 0,
                        'position' => $standing['position'] ?? null,
                        'profile' => null,
                        'standing' => $standing,
                    ];
                }
            }
        }

        return $players;
    }

    /**
     * Get detailed round-by-round scores for players
     */
    public function getPlayerRoundScores(string $tournamentId, int $playerId): array
    {
        $games = $this->getPlayerStats($tournamentId, $playerId);
        $rounds = $this->getTournamentRounds($tournamentId);
        
        $roundScores = [];
        
        foreach ($rounds as $round) {
            $roundGames = collect($games['data'] ?? [])
                ->where('roundId', $round['roundId']);
            
            $roundScore = [
                'roundId' => $round['roundId'],
                'roundNumber' => $round['index'] + 1,
                'roundName' => $round['name'],
                'points' => $roundGames->sum('points'),
                'gamesPlayed' => $roundGames->count(),
                'games' => $roundGames->toArray(),
            ];
            
            $roundScores[] = $roundScore;
        }
        
        return $roundScores;
    }

    /**
     * Make authenticated request to Matchplay API
     */
    private function makeRequest(string $endpoint, array $params = []): Response
    {
        $url = self::BASE_URL . '/' . ltrim($endpoint, '/');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->user->matchplay_api_token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get($url, $params);

        if (!$response->successful()) {
            Log::warning('Matchplay API request failed', [
                'endpoint' => $endpoint,
                'params' => $params,
                'status' => $response->status(),
                'body' => $response->body(),
                'user_id' => $this->user->id,
            ]);
        }

        return $response;
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest('dashboard');
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Matchplay API connection test failed', [
                'error' => $e->getMessage(),
                'user_id' => $this->user->id,
            ]);
            return false;
        }
    }

    /**
     * Get rate limit information from last response
     */
    public function getRateLimitInfo(): array
    {
        // This would be populated from the last response headers
        // The API docs show rate limiting headers in responses
        return [
            'limit' => null,
            'remaining' => null,
            'reset_time' => null,
        ];
    }
}
