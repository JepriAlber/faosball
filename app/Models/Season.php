<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends FaosModel
{
    use HasFactory;

    protected $table = 'seasons';
    protected $primaryKey = 'id_season';

    protected $fillable = ['id_academy', 'name', 'start_date', 'end_date', 'status'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => 'boolean',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'id_season', 'id_season');
    }
}
