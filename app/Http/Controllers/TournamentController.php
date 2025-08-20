<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Round;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\MatchplayApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return Inertia::render('Tournaments/Index', [
            'tournaments' => $tournaments,
        ]);
    }

    public function create()
    {
        if (!auth()->user()->hasMatchplayToken()) {
            return redirect()->route('profile.edit')
                ->with('error', 'Please add your Matchplay API token first.');
        }

        return Inertia::render('Tournaments/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'matchplay_tournament_id' => 'required|string|unique:tournaments,matchplay_tournament_id',
        ]);

        try {
            $matchplayService = new MatchplayApiService(auth()->user());
            
            // Verify tournament exists and get data
            $tournamentData = $matchplayService->getTournament($request->matchplay_tournament_id);
            if (!$tournamentData) {
                throw ValidationException::withMessages([
                    'matchplay_tournament_id' => 'Tournament not found in Matchplay API.',
                ]);
            }

            // Handle nested data structure from Matchplay API
            $data = $tournamentData['data'] ?? $tournamentData;

            $tournament = Tournament::create([
                'user_id' => auth()->id(),
                'matchplay_tournament_id' => $request->matchplay_tournament_id,
                'name' => $data['name'] ?? 'Unnamed Tournament',
                'description' => $data['description'] ?? null,
                'start_date' => isset($data['startUtc']) ? now()->parse($data['startUtc']) : null,
                'end_date' => isset($data['endUtc']) ? now()->parse($data['endUtc']) : null,
                'status' => $data['status'] ?? 'active',
                'matchplay_data' => $tournamentData,
            ]);

            // Import players and create rounds
            $this->importTournamentData($tournament);

            return redirect()->route('tournaments.show', $tournament)
                ->with('success', 'Tournament created successfully!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create tournament: ' . $e->getMessage()]);
        }
    }

    public function show(Tournament $tournament)
    {
        $this->authorize('view', $tournament);

        $tournament->load([
            'teams.player1',
            'teams.player2', 
            'teams.roundScores.round',
            'rounds'
        ]);

        // Get all available players (imported from Matchplay)
        $availablePlayers = Player::orderBy('name')->get();

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
            return back()->withErrors(['error' => 'Failed to sync tournament: ' . $e->getMessage()]);
        }
    }

    private function importTournamentData(Tournament $tournament): void
    {
        $matchplayService = new MatchplayApiService($tournament->user);

        // Import players from standings
        $playersData = $matchplayService->getTournamentPlayers($tournament->matchplay_tournament_id);
        foreach ($playersData as $playerData) {
            Player::updateOrCreate(
                ['matchplay_player_id' => $playerData['playerId']],
                [
                    'name' => $playerData['name'],
                    'ifpa_id' => $playerData['ifpaId'],
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
                    'matchplay_round_id' => $roundData['roundId']
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

    private function updateTeamScores(Tournament $tournament): void
    {
        $matchplayService = new MatchplayApiService($tournament->user);
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
