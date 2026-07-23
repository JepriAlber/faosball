<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends FaosModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'teams';
    protected $primaryKey = 'id_team';

    protected $fillable = [
        'id_academy', 'id_season', 'id_player_category',
        'code', 'name', 'team_type', 'description', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'id_season', 'id_season');
    }

    public function playerCategory(): BelongsTo
    {
        return $this->belongsTo(PlayerCategory::class, 'id_player_category', 'id_player_category');
    }

    /**
     * Seluruh histori Team Player tim ini (aktif maupun sudah keluar).
     * "Aktif" = leave_date IS NULL (issue16.md Bagian 2c), BUKAN kolom
     * status terpisah.
     */
    public function teamPlayers(): HasMany
    {
        return $this->hasMany(TeamPlayer::class, 'id_team', 'id_team')->latest('join_date');
    }

    public function activeTeamPlayers(): HasMany
    {
        return $this->teamPlayers()->whereNull('leave_date');
    }

    public function teamStaff(): HasMany
    {
        return $this->hasMany(TeamStaff::class, 'id_team', 'id_team')->latest('join_date');
    }

    public function activeTeamStaff(): HasMany
    {
        return $this->teamStaff()->whereNull('leave_date');
    }
}
