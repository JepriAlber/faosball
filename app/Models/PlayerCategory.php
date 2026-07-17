<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PlayerCategory extends FaosModel, jadi otomatis dapat:
 * - UUID primary key
 * - BelongsToAcademy (AcademyScope + isi id_academy saat creating)
 *
 * Sama seperti PlayerType, model ini MEMANG BOLEH pakai global scope --
 * larangan global scope hanya berlaku untuk App\Models\Role, karena alasan
 * cache Spatie. Lihat issue.md Bagian 4.3.
 */
class PlayerCategory extends FaosModel
{
    use HasFactory;

    protected $table = 'player_categories';
    protected $primaryKey = 'id_player_category';

    protected $fillable = [
        // id_academy wajib fillable supaya Super Admin bisa memilih academy.
        'id_academy',
        'name',
        'description',
        'min_age',
        'max_age',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'min_age' => 'integer',
            'max_age' => 'integer',
            'status' => 'boolean',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'id_player_category', 'id_player_category');
    }
}
