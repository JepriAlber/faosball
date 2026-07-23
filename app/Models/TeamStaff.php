<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamStaff extends FaosModel
{
    use HasFactory;

    protected $table = 'team_staff';
    protected $primaryKey = 'id_team_staff';

    protected $fillable = [
        'id_academy', 'id_team', 'id_staff', 'id_team_staff_position',
        'join_date', 'leave_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'leave_date' => 'date',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'id_team', 'id_team');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'id_staff', 'id_staff');
    }

    public function teamStaffPosition(): BelongsTo
    {
        return $this->belongsTo(TeamStaffPosition::class, 'id_team_staff_position', 'id_team_staff_position');
    }

    public function isActive(): bool
    {
        return $this->leave_date === null;
    }
}
