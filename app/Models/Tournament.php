<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tournament extends Model
{
    protected $fillable = [
        'user_id',
        'matchplay_tournament_id',
        'name',
        'status',
        'team_size',
        'description',
        'start_date',
        'end_date',
        'matchplay_data',
        'qr_code_uuid',
        'auto_sync',
        'tournament_player_ids',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'matchplay_data' => 'array',
            'auto_sync' => 'boolean',
            'tournament_player_ids' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($tournament) {
            if (empty($tournament->qr_code_uuid)) {
                $tournament->qr_code_uuid = Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }

    public function players()
    {
        return Player::whereIn('id', function ($query) {
            $query->select('player1_id')
                ->from('teams')
                ->where('tournament_id', $this->id)
                ->union(
                    $query->newQuery()
                        ->select('player2_id')
                        ->from('teams')
                        ->where('tournament_id', $this->id)
                )
                ->union(
                    $query->newQuery()
                        ->select('player3_id')
                        ->from('teams')
                        ->where('tournament_id', $this->id)
                        ->whereNotNull('player3_id')
                )
                ->union(
                    $query->newQuery()
                        ->select('player4_id')
                        ->from('teams')
                        ->where('tournament_id', $this->id)
                        ->whereNotNull('player4_id')
                );
        });
    }

    public function getQrCodeUrlAttribute(): string
    {
        return route('tournaments.leaderboard.public', $this->qr_code_uuid);
    }

    public function calculateStandings(): array
    {
        return $this->teams()
            ->with(['player1', 'player2', 'player3', 'player4', 'roundScores'])
            ->get()
            ->map(function ($team) {
                // Calculate real-time totals from round scores
                $calculatedTotalPoints = $team->roundScores->sum('total_points');
                $calculatedGamesPlayed = $team->roundScores->sum('player1_games_played') +
                                       $team->roundScores->sum('player2_games_played') +
                                       $team->roundScores->sum('player3_games_played') +
                                       $team->roundScores->sum('player4_games_played');

                // Use calculated values if they exist, otherwise fall back to stored values
                $totalPoints = $calculatedTotalPoints > 0 ? $calculatedTotalPoints : $team->total_points;
                $gamesPlayed = $calculatedGamesPlayed > 0 ? $calculatedGamesPlayed : $team->games_played;

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'generated_name' => $team->generated_name,
                    'total_points' => $totalPoints,
                    'games_played' => $gamesPlayed,
                    'player1' => $team->player1,
                    'player2' => $team->player2,
                    'player3' => $team->player3,
                    'player4' => $team->player4,
                ];
            })
            ->sortByDesc('total_points')
            ->sortByDesc('games_played')
            ->values()
            ->map(function ($team, $index) {
                $team['position'] = $index + 1;

                return $team;
            })
            ->toArray();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
