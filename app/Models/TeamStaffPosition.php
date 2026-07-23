<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamStaffPosition extends FaosModel
{
    use HasFactory;

    protected $table = 'team_staff_positions';
    protected $primaryKey = 'id_team_staff_position';

    protected $fillable = ['id_academy', 'code', 'name', 'description', 'status'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function teamStaff(): HasMany
    {
        return $this->hasMany(TeamStaff::class, 'id_team_staff_position', 'id_team_staff_position');
    }
}
