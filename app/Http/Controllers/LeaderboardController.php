<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    public function public(string $qrCodeUuid)
    {
        $tournament = Tournament::where('qr_code_uuid', $qrCodeUuid)
            ->with(['teams.player1', 'teams.player2', 'teams.roundScores.round', 'rounds'])
            ->firstOrFail();

        $standings = $tournament->calculateStandings();
        
        // Enhance standings with individual player scores and round data
        $enhancedStandings = collect($standings)->map(function ($standing) use ($tournament) {
            $team = $tournament->teams->find($standing['id']);
            if (!$team) return $standing;
            
            // Get individual player data from Matchplay API if available
            $player1Data = $this->getPlayerScoreData($tournament, $team->player1);
            $player2Data = $this->getPlayerScoreData($tournament, $team->player2);
            
            // Get round-by-round scores
            $roundScores = $team->roundScores->map(function ($roundScore) {
                return [
                    'round_number' => $roundScore->round->round_number,
                    'round_name' => $roundScore->round->name,
                    'player1_points' => $roundScore->player1_points,
                    'player2_points' => $roundScore->player2_points,
                    'total_points' => $roundScore->total_points,
                    'round_status' => $roundScore->round->status,
                ];
            })->sortBy('round_number')->values();
            
            return array_merge($standing, [
                'player1_individual_score' => $player1Data['points'] ?? 0,
                'player1_games_played' => $player1Data['gamesPlayed'] ?? 0,
                'player2_individual_score' => $player2Data['points'] ?? 0,
                'player2_games_played' => $player2Data['gamesPlayed'] ?? 0,
                'round_scores' => $roundScores,
                'is_in_progress' => $team->roundScores->some(fn($rs) => $rs->round->status === 'active'),
            ]);
        })->toArray();

        $completedRounds = $tournament->rounds()
            ->where('status', 'completed')
            ->orderBy('round_number')
            ->get();
            
        $allRounds = $tournament->rounds()
            ->orderBy('round_number')
            ->get();

        return Inertia::render('leaderboard/public', [
            'tournament' => $tournament,
            'standings' => $enhancedStandings,
            'completedRounds' => $completedRounds,
            'allRounds' => $allRounds,
            'lastUpdated' => now()->toISOString(),
        ]);
    }

    public function refresh(string $qrCodeUuid)
    {
        $tournament = Tournament::where('qr_code_uuid', $qrCodeUuid)->firstOrFail();
        
        // Update team scores from Matchplay API
        $this->updateTeamScores($tournament);
        
        // If this is an AJAX request, return JSON data
        if (request()->wantsJson()) {
            $tournament->load(['teams.player1', 'teams.player2', 'teams.roundScores.round', 'rounds']);
            $standings = $tournament->calculateStandings();
            
            // Enhance standings with individual player scores and round data
            $enhancedStandings = collect($standings)->map(function ($standing) use ($tournament) {
                $team = $tournament->teams->find($standing['id']);
                if (!$team) return $standing;
                
                $player1Data = $this->getPlayerScoreData($tournament, $team->player1);
                $player2Data = $this->getPlayerScoreData($tournament, $team->player2);
                
                $roundScores = $team->roundScores->map(function ($roundScore) {
                    return [
                        'round_number' => $roundScore->round->round_number,
                        'round_name' => $roundScore->round->name,
                        'player1_points' => $roundScore->player1_points,
                        'player2_points' => $roundScore->player2_points,
                        'total_points' => $roundScore->total_points,
                        'round_status' => $roundScore->round->status,
                    ];
                })->sortBy('round_number')->values();
                
                return array_merge($standing, [
                    'player1_individual_score' => $player1Data['points'] ?? 0,
                    'player1_games_played' => $player1Data['gamesPlayed'] ?? 0,
                    'player2_individual_score' => $player2Data['points'] ?? 0,
                    'player2_games_played' => $player2Data['gamesPlayed'] ?? 0,
                    'round_scores' => $roundScores,
                    'is_in_progress' => $team->roundScores->some(fn($rs) => $rs->round->status === 'active'),
                ]);
            })->toArray();

            return response()->json([
                'standings' => $enhancedStandings,
                'lastUpdated' => now()->toISOString(),
            ]);
        }

        return redirect()->route('tournaments.leaderboard.public', $qrCodeUuid);
    }

    private function getPlayerScoreData(Tournament $tournament, $player): array
    {
        try {
            $matchplayService = new \App\Services\MatchplayApiService($tournament->user);
            $standings = $matchplayService->getTournamentStandings($tournament->matchplay_tournament_id);
            
            $playerStanding = collect($standings)->firstWhere('playerId', $player->matchplay_player_id);
            
            return [
                'points' => $playerStanding['points'] ?? 0,
                'gamesPlayed' => $playerStanding['gamesPlayed'] ?? 0,
                'position' => $playerStanding['position'] ?? null,
            ];
        } catch (\Exception $e) {
            return ['points' => 0, 'gamesPlayed' => 0, 'position' => null];
        }
    }

    private function updateTeamScores(Tournament $tournament): void
    {
        $matchplayService = new \App\Services\MatchplayApiService($tournament->user);
        $standings = $matchplayService->getTournamentStandings($tournament->matchplay_tournament_id);

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
            }
        }
    }
}
