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

        $tournament->load(['teams.player1', 'teams.player2']);
        
        // Get all players from the tournament using stored player IDs
        $playerIds = $tournament->tournament_player_ids ?? [];
        $allPlayersInTournament = Player::whereIn('matchplay_player_id', $playerIds)->get();

        // Get available players (not on teams yet)
        $availablePlayers = $allPlayersInTournament->filter(function ($player) use ($tournament) {
            return !$player->isOnTeamInTournament($tournament->id);
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

        $request->validate([
            'player1_id' => 'required|exists:players,id',
            'player2_id' => 'required|exists:players,id|different:player1_id',
            'custom_name' => 'nullable|string|max:255',
        ]);

        $player1 = Player::findOrFail($request->player1_id);
        $player2 = Player::findOrFail($request->player2_id);

        // Check if players are already on teams in this tournament
        if ($player1->isOnTeamInTournament($tournament->id)) {
            throw ValidationException::withMessages([
                'player1_id' => $player1->name . ' is already on a team in this tournament.',
            ]);
        }

        if ($player2->isOnTeamInTournament($tournament->id)) {
            throw ValidationException::withMessages([
                'player2_id' => $player2->name . ' is already on a team in this tournament.',
            ]);
        }

        $teamNameGenerator = new TeamNameGeneratorService();
        $generatedName = $teamNameGenerator->generate();

        $team = Team::create([
            'tournament_id' => $tournament->id,
            'player1_id' => $player1->id,
            'player2_id' => $player2->id,
            'name' => $request->custom_name ?: $generatedName,
            'generated_name' => $generatedName,
        ]);

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

        $teamNameGenerator = new TeamNameGeneratorService();
        $newName = $teamNameGenerator->generate();

        $team->update([
            'name' => $newName,
            'generated_name' => $newName,
        ]);

        return back()->with('success', 'Team name regenerated!');
    }
}
