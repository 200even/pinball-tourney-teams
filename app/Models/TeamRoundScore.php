<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamRoundScore extends Model
{
    protected $fillable = [
        'team_id',
        'round_id',
        'player1_points',
        'player2_points',
        'total_points',
        'player1_games_played',
        'player2_games_played',
        'games_data',
    ];

    protected function casts(): array
    {
        return [
            'player1_points' => 'decimal:2',
            'player2_points' => 'decimal:2',
            'total_points' => 'decimal:2',
            'games_data' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($teamRoundScore) {
            $teamRoundScore->total_points = $teamRoundScore->player1_points + $teamRoundScore->player2_points;
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }
}
