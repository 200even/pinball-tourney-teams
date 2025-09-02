<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use App\Services\MatchplayApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTournaments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tournaments:sync {--active-only : Only sync active tournaments} {--tournament= : Sync specific tournament ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync tournament data and team scores from Matchplay API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = now();
        $this->info('Starting tournament sync...');

        // Determine which tournaments to sync
        if ($tournamentId = $this->option('tournament')) {
            $tournaments = Tournament::where('id', $tournamentId)->get();
            if ($tournaments->isEmpty()) {
                $this->error("Tournament with ID {$tournamentId} not found.");

                return 1;
            }
        } elseif ($this->option('active-only')) {
            $tournaments = Tournament::whereIn('status', ['active', 'completed'])
                ->where('auto_sync', true)
                ->with(['user', 'teams.player1', 'teams.player2'])
                ->get();
        } else {
            $tournaments = Tournament::with(['user', 'teams.player1', 'teams.player2'])->get();
        }

        if ($tournaments->isEmpty()) {
            $this->info('No tournaments found to sync.');

            return 0;
        }

        $this->info("Found {$tournaments->count()} tournament(s) to sync.");

        $syncedCount = 0;
        $errorCount = 0;
        $bar = $this->output->createProgressBar($tournaments->count());

        foreach ($tournaments as $tournament) {
            try {
                $this->syncTournament($tournament);
                $syncedCount++;
                $this->line("  ✓ Synced: {$tournament->name}");
            } catch (\Exception $e) {
                $errorCount++;
                $this->line("  ✗ Failed: {$tournament->name} - {$e->getMessage()}");

                Log::error('Tournament sync failed', [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $duration = $startTime->diffInSeconds(now());
        $this->info("Sync completed in {$duration} seconds:");
        $this->info("  ✓ Synced: {$syncedCount} tournaments");

        if ($errorCount > 0) {
            $this->warn("  ✗ Failed: {$errorCount} tournaments");
        }

        return $errorCount > 0 ? 1 : 0;
    }

    private function syncTournament(Tournament $tournament): void
    {
        if (! $tournament->user->hasMatchplayToken()) {
            throw new \Exception("User {$tournament->user->name} has no Matchplay API token");
        }

        $matchplayService = new MatchplayApiService($tournament->user);

        // Update tournament status
        $tournamentData = $matchplayService->getTournament($tournament->matchplay_tournament_id);
        if ($tournamentData) {
            $tournament->update([
                'status' => $tournamentData['status'] ?? $tournament->status,
                'matchplay_data' => $tournamentData,
            ]);
        }

        // Update team scores if there are teams
        if ($tournament->teams->isNotEmpty()) {
            $this->updateTeamScores($tournament, $matchplayService);
        }
    }

    private function updateTeamScores(Tournament $tournament, MatchplayApiService $matchplayService): void
    {
        $standings = $matchplayService->getTournamentStandings($tournament->matchplay_tournament_id);

        // Update round-by-round scores for all teams
        $this->syncTeamRoundScores($tournament, $matchplayService);

        $updatedTeams = 0;
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

                $updatedTeams++;
            }
        }

        if ($updatedTeams > 0) {
            $this->line("    Updated {$updatedTeams} team scores");
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

            $this->line("    Syncing round-by-round scores for {$completedRounds->count()} completed rounds");

            foreach ($tournament->teams as $team) {
                // Get round-by-round data for both players
                $player1RoundScores = $matchplayService->getPlayerRoundScores(
                    $tournament->matchplay_tournament_id,
                    (int) $team->player1->matchplay_player_id
                );

                $player2RoundScores = $matchplayService->getPlayerRoundScores(
                    $tournament->matchplay_tournament_id,
                    (int) $team->player2->matchplay_player_id
                );

                // Index round scores by round number for easy lookup
                $player1ScoresByRound = collect($player1RoundScores)->keyBy('roundNumber');
                $player2ScoresByRound = collect($player2RoundScores)->keyBy('roundNumber');

                // Update team round scores for each completed round
                foreach ($completedRounds as $round) {
                    $player1RoundData = $player1ScoresByRound->get($round->round_number, []);
                    $player2RoundData = $player2ScoresByRound->get($round->round_number, []);

                    $player1Points = $player1RoundData['points'] ?? 0;
                    $player2Points = $player2RoundData['points'] ?? 0;
                    $player1Games = $player1RoundData['gamesPlayed'] ?? 0;
                    $player2Games = $player2RoundData['gamesPlayed'] ?? 0;

                    // Create or update the team round score record
                    \App\Models\TeamRoundScore::updateOrCreate(
                        [
                            'team_id' => $team->id,
                            'round_id' => $round->id,
                        ],
                        [
                            'player1_points' => $player1Points,
                            'player2_points' => $player2Points,
                            'player1_games_played' => $player1Games,
                            'player2_games_played' => $player2Games,
                            'games_data' => [
                                'player1_games' => $player1RoundData['games'] ?? [],
                                'player2_games' => $player2RoundData['games'] ?? [],
                            ],
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the entire update process
            $this->error('    Failed to sync team round scores: '.$e->getMessage());
        }
    }
}
