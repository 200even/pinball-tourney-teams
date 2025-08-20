<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'tournament_id',
        'player1_id',
        'player2_id',
        'name',
        'generated_name',
        'total_points',
        'games_played',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'total_points' => 'decimal:2',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player2_id');
    }

    public function roundScores(): HasMany
    {
        return $this->hasMany(TeamRoundScore::class);
    }

    public function players()
    {
        return collect([$this->player1, $this->player2]);
    }

    public function hasPlayer(Player $player): bool
    {
        return $this->player1_id === $player->id || $this->player2_id === $player->id;
    }

    public function getOtherPlayer(Player $player): ?Player
    {
        if ($this->player1_id === $player->id) {
            return $this->player2;
        }
        
        if ($this->player2_id === $player->id) {
            return $this->player1;
        }
        
        return null;
    }

    public function updateTotalPoints(): void
    {
        $totalPoints = $this->roundScores()->sum('total_points');
        $gamesPlayed = $this->roundScores()->sum('player1_games_played') + 
                      $this->roundScores()->sum('player2_games_played');

        $this->update([
            'total_points' => $totalPoints,
            'games_played' => $gamesPlayed,
        ]);
    }
}
