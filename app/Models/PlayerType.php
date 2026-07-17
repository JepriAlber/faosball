<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PlayerType extends FaosModel, jadi otomatis dapat:
 * - UUID primary key
 * - BelongsToAcademy (AcademyScope + isi id_academy saat creating)
 *
 * Berbeda dengan App\Models\Role, model ini MEMANG BOLEH pakai global scope.
 * Lihat Bagian 4.3 di issue.md.
 */
class PlayerType extends FaosModel
{
    use HasFactory;

    protected $table = 'player_types';
    protected $primaryKey = 'id_player_type';

    protected $fillable = [
        // id_academy wajib fillable supaya Super Admin bisa memilih academy.
        // Tanpa ini, nilainya didiamkan sebelum sempat dibaca BelongsToAcademy.
        'id_academy',
        'name',
        'description',
        'is_billable',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_billable' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'id_player_type', 'id_player_type');
    }
}
