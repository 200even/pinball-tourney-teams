<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    protected $fillable = [
        'matchplay_player_id',
        'name',
        'ifpa_id',
        'matchplay_data',
    ];

    protected function casts(): array
    {
        return [
            'matchplay_data' => 'array',
        ];
    }

    public function teamsAsPlayer1(): HasMany
    {
        return $this->hasMany(Team::class, 'player1_id');
    }

    public function teamsAsPlayer2(): HasMany
    {
        return $this->hasMany(Team::class, 'player2_id');
    }

    public function teams()
    {
        return Team::where('player1_id', $this->id)
            ->orWhere('player2_id', $this->id);
    }

    public function isOnTeamInTournament(int $tournamentId): bool
    {
        return Team::where('tournament_id', $tournamentId)
            ->where(function ($query) {
                $query->where('player1_id', $this->id)
                      ->orWhere('player2_id', $this->id);
            })->exists();
    }

    public function getTeamInTournament(int $tournamentId): ?Team
    {
        return Team::where('tournament_id', $tournamentId)
            ->where(function ($query) {
                $query->where('player1_id', $this->id)
                      ->orWhere('player2_id', $this->id);
            })->first();
    }
}
