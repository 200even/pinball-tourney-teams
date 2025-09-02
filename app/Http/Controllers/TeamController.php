<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\TeamNameGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(Tournament $tournament)
    {
        $this->authorize('view', $tournament);

        $tournament->load(['teams.player1', 'teams.player2', 'teams.player3', 'teams.player4']);

        // Get all players from the tournament using stored player IDs
        $playerIds = $tournament->tournament_player_ids ?? [];
        $allPlayersInTournament = Player::whereIn('matchplay_player_id', $playerIds)->get();

        // Get available players (not on teams yet)
        $availablePlayers = $allPlayersInTournament->filter(function ($player) use ($tournament) {
            return ! $player->isOnTeamInTournament($tournament->id);
        });

        return Inertia::render('Teams/Index', [
            'tournament' => $tournament,
            'teams' => $tournament->teams,
            'availablePlayers' => $availablePlayers->values(),
        ]);
    }

    public function store(Request $request, Tournament $tournament)
    {
        $this->authorize('update', $tournament);

        $validationRules = [
            'player1_id' => 'required|exists:players,id',
            'player2_id' => 'required|exists:players,id|different:player1_id',
            'custom_name' => 'nullable|string|max:255',
        ];

        if ($tournament->team_size === 4) {
            $validationRules['player3_id'] = 'required|exists:players,id|different:player1_id,player2_id';
            $validationRules['player4_id'] = 'required|exists:players,id|different:player1_id,player2_id,player3_id';
        }

        $request->validate($validationRules);

        $player1 = Player::findOrFail($request->player1_id);
        $player2 = Player::findOrFail($request->player2_id);
        $player3 = $tournament->team_size === 4 ? Player::findOrFail($request->player3_id) : null;
        $player4 = $tournament->team_size === 4 ? Player::findOrFail($request->player4_id) : null;

        // Check if players are already on teams in this tournament
        $players = collect([$player1, $player2, $player3, $player4])->filter();

        foreach ($players as $index => $player) {
            if ($player->isOnTeamInTournament($tournament->id)) {
                $fieldName = 'player'.($index + 1).'_id';
                throw ValidationException::withMessages([
                    $fieldName => $player->name.' is already on a team in this tournament.',
                ]);
            }
        }

        $teamNameGenerator = new TeamNameGeneratorService;
        $generatedName = $teamNameGenerator->generate();

        $teamData = [
            'tournament_id' => $tournament->id,
            'player1_id' => $player1->id,
            'player2_id' => $player2->id,
            'name' => $request->custom_name ?: $generatedName,
            'generated_name' => $generatedName,
        ];

        if ($tournament->team_size === 4) {
            $teamData['player3_id'] = $player3->id;
            $teamData['player4_id'] = $player4->id;
        }

        $team = Team::create($teamData);

        return redirect()->route('tournaments.teams.index', $tournament)
            ->with('success', 'Team created successfully!');
    }

    public function update(Request $request, Tournament $tournament, Team $team)
    {
        $this->authorize('update', $tournament);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $team->update([
            'name' => $request->name,
        ]);

        return back()->with('success', 'Team name updated successfully!');
    }

    public function destroy(Tournament $tournament, Team $team)
    {
        $this->authorize('update', $tournament);

        $team->delete();

        return back()->with('success', 'Team deleted successfully!');
    }

    public function regenerateName(Tournament $tournament, Team $team)
    {
        $this->authorize('update', $tournament);

        $teamNameGenerator = new TeamNameGeneratorService;
        $newName = $teamNameGenerator->generate();

        $team->update([
            'name' => $newName,
            'generated_name' => $newName,
        ]);

        return back()->with('success', 'Team name regenerated!');
    }
}
