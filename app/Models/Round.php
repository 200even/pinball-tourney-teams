<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    protected $fillable = [
        'tournament_id',
        'matchplay_round_id',
        'round_number',
        'name',
        'status',
        'completed_at',
        'matchplay_data',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'matchplay_data' => 'array',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function teamRoundScores(): HasMany
    {
        return $this->hasMany(TeamRoundScore::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
