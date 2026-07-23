<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamPlayer extends FaosModel
{
    use HasFactory;

    protected $table = 'team_players';
    protected $primaryKey = 'id_team_player';

    protected $fillable = [
        'id_academy', 'id_team', 'id_player',
        'jersey_number', 'is_captain', 'join_date', 'leave_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'jersey_number' => 'integer',
            'is_captain' => 'boolean',
            'join_date' => 'date',
            'leave_date' => 'date',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'id_team', 'id_team');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'id_player', 'id_player');
    }

    public function isActive(): bool
    {
        return $this->leave_date === null;
    }
}
