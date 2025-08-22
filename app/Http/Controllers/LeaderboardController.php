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
            ->with(['teams.player1', 'teams.player2', 'rounds'])
            ->firstOrFail();

        $standings = $tournament->calculateStandings();

        $completedRounds = $tournament->rounds()
            ->where('status', 'completed')
            ->orderBy('round_number')
            ->get();

        return Inertia::render('Leaderboard/Public', [
            'tournament' => $tournament,
            'standings' => $standings,
            'completedRounds' => $completedRounds,
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
            $standings = $tournament->calculateStandings();

            return response()->json([
                'standings' => $standings,
                'lastUpdated' => now()->toISOString(),
            ]);
        }

        return redirect()->route('tournaments.leaderboard.public', $qrCodeUuid);
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
