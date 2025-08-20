<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MatchplayApiService;
use Illuminate\Console\Command;

class TestMatchplayApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matchplay:test {user_id} {tournament_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Matchplay API integration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->argument('user_id');
        $tournamentId = $this->argument('tournament_id');

        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        if (!$user->hasMatchplayToken()) {
            $this->error("User {$user->name} does not have a Matchplay API token.");
            return 1;
        }

        try {
            $matchplayService = new MatchplayApiService($user);

            $this->info("Testing Matchplay API for user: {$user->name}");
            $this->line('');

            // Test connection
            $this->info('Testing API connection...');
            if ($matchplayService->testConnection()) {
                $this->info('✓ API connection successful');
            } else {
                $this->error('✗ API connection failed');
                return 1;
            }

            // Test dashboard
            $this->info('Getting dashboard data...');
            $dashboard = $matchplayService->getDashboard();
            if (!empty($dashboard)) {
                $this->info('✓ Dashboard data retrieved');
                $this->line('Dashboard keys: ' . implode(', ', array_keys($dashboard)));
            } else {
                $this->warn('Dashboard data is empty');
            }

            // Test specific tournament if provided
            if ($tournamentId) {
                $this->line('');
                $this->info("Testing tournament {$tournamentId}...");

                $tournament = $matchplayService->getTournament($tournamentId);
                if ($tournament) {
                    $this->info('✓ Tournament data retrieved');
                    $this->line("Tournament: " . ($tournament['name'] ?? 'Unknown'));

                    $standings = $matchplayService->getTournamentStandings($tournamentId);
                    $this->info("✓ Standings retrieved (" . count($standings) . " players)");

                    $rounds = $matchplayService->getTournamentRounds($tournamentId);
                    $this->info("✓ Rounds retrieved (" . count($rounds) . " rounds)");

                    $players = $matchplayService->getTournamentPlayers($tournamentId);
                    $this->info("✓ Players retrieved (" . count($players) . " players)");

                    // Show sample data
                    if (!empty($players)) {
                        $this->line('');
                        $this->info('Sample players:');
                        foreach (array_slice($players, 0, 3) as $player) {
                            $this->line("  - {$player['name']} (ID: {$player['playerId']}, Points: {$player['points']})");
                        }
                    }
                } else {
                    $this->error("✗ Tournament {$tournamentId} not found");
                    return 1;
                }
            }

            $this->line('');
            $this->info('🎉 Matchplay API integration test completed successfully!');

        } catch (\Exception $e) {
            $this->error("API test failed: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
