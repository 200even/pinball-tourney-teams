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
        if (! $user->hasMatchplayToken()) {
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
        $response = $this->makeRequest("users/{$playerId}");

        if ($response->successful()) {
            $data = $response->json();

            return $data['user'] ?? null;
        }

        return null;
    }

    /**
     * Get players owned by the current user
     */
    public function getPlayers(?string $status = null, ?array $playerIds = null): array
    {
        $queryParams = [];

        if ($status) {
            $queryParams['status'] = $status;
        }

        if ($playerIds && is_array($playerIds)) {
            // Limit to 25 players per API docs
            $playerIds = array_slice($playerIds, 0, 25);
            $queryParams['players'] = implode(',', $playerIds);
        }

        $response = $this->makeRequest('players', 'GET', $queryParams);

        if ($response->successful()) {
            $data = $response->json();

            return $data['data'] ?? [];
        }

        return [];
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
     * Extract players from tournament standings with improved name resolution
     */
    public function getTournamentPlayers(string $tournamentId): array
    {
        $standings = $this->getTournamentStandings($tournamentId);
        $players = [];

        // Extract player IDs from standings
        $playerIds = array_map(fn ($standing) => $standing['playerId'], $standings);

        // Try to get player details in batches of 25 (API limit)
        $playerProfiles = [];
        $chunks = array_chunk($playerIds, 25);

        foreach ($chunks as $chunk) {
            $profiles = $this->getPlayers(null, $chunk);
            foreach ($profiles as $profile) {
                $playerProfiles[$profile['playerId']] = $profile;
            }
        }

        // Combine standings with player profiles
        foreach ($standings as $standing) {
            if (isset($standing['playerId'])) {
                $playerId = $standing['playerId'];
                $profile = $playerProfiles[$playerId] ?? null;

                $players[] = [
                    'playerId' => $playerId,
                    'name' => $profile['name'] ?? "Player {$playerId}",
                    'ifpaId' => $profile['ifpaId'] ?? null,
                    'points' => $standing['points'] ?? 0,
                    'gamesPlayed' => $standing['gamesPlayed'] ?? 0,
                    'position' => $standing['position'] ?? null,
                    'profile' => $profile,
                    'standing' => $standing,
                ];
            }
        }

        return $players;
    }

    /**
     * Get detailed round-by-round scores for players
     */
    public function getPlayerRoundScores(string $tournamentId, int $playerId): array
    {
        try {
            // Get all tournament games instead of player-specific endpoint
            $allGames = $this->getTournamentGames($tournamentId);
            $rounds = $this->getTournamentRounds($tournamentId);

            if (empty($allGames) || empty($rounds)) {
                return [];
            }

            // Filter games for this specific player
            $playerGames = collect($allGames)->filter(function ($game) use ($playerId) {
                // Check if player is in this game using playerIds array
                $playerIds = $game['playerIds'] ?? [];

                return in_array($playerId, $playerIds);
            });

            $roundScores = [];

            foreach ($rounds as $round) {
                // Filter player's games for this specific round
                $roundGames = $playerGames->where('roundId', $round['roundId']);

                // Calculate points for this player in this round
                $roundPoints = 0;
                $roundGamesCount = 0;
                $gameDetails = [];

                foreach ($roundGames as $game) {
                    // Find this player's position in the playerIds array
                    $playerIds = $game['playerIds'] ?? [];
                    $playerIndex = array_search($playerId, $playerIds);

                    if ($playerIndex !== false) {
                        // Get the corresponding points and position for this player
                        $points = ($game['resultPoints'] ?? [])[$playerIndex] ?? 0;
                        $position = ($game['resultPositions'] ?? [])[$playerIndex] ?? null;

                        $roundPoints += $points;
                        $roundGamesCount++;
                        $gameDetails[] = [
                            'gameId' => $game['gameId'] ?? null,
                            'points' => $points,
                            'position' => $position,
                        ];
                    }
                }

                $roundScore = [
                    'roundId' => $round['roundId'],
                    'roundNumber' => $round['index'] + 1,
                    'roundName' => $round['name'],
                    'points' => $roundPoints,
                    'gamesPlayed' => $roundGamesCount,
                    'games' => $gameDetails,
                ];

                $roundScores[] = $roundScore;
            }

            return $roundScores;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to get player round scores', [
                'tournament_id' => $tournamentId,
                'player_id' => $playerId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get tournament player roster (different from standings)
     */
    public function getTournamentPlayerRoster(string $tournamentId): array
    {
        $response = $this->makeRequest("tournaments/{$tournamentId}/players");

        if ($response->successful()) {
            $data = $response->json();

            return $data['data'] ?? $data;
        }

        return [];
    }

    /**
     * Make authenticated request to Matchplay API
     */
    private function makeRequest(string $endpoint, string $method = 'GET', array $params = []): Response
    {
        $url = self::BASE_URL.'/'.ltrim($endpoint, '/');

        $httpClient = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->user->matchplay_api_token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        $response = match (strtoupper($method)) {
            'GET' => $httpClient->get($url, $params),
            'POST' => $httpClient->post($url, $params),
            'PUT' => $httpClient->put($url, $params),
            'DELETE' => $httpClient->delete($url, $params),
            default => $httpClient->get($url, $params),
        };

        if (! $response->successful()) {
            Log::warning('Matchplay API request failed', [
                'endpoint' => $endpoint,
                'method' => $method,
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
