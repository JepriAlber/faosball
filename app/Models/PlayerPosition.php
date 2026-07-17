<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Master posisi pemain -- DATA GLOBAL.
 *
 * PERHATIKAN: model ini sengaja `extends Model`, BUKAN `extends FaosModel`.
 *
 * FaosModel memaksa trait BelongsToAcademy (AcademyScope + isi id_academy saat
 * creating). Itu benar untuk PlayerType & PlayerCategory yang memang milik
 * masing-masing academy, tapi SALAH untuk master global seperti ini: tabelnya
 * tidak punya kolom id_academy sama sekali, sehingga AcademyScope akan
 * menghasilkan SQL error "column not found" pada setiap query dari user academy.
 *
 * Konsekuensinya, UUID generation yang biasanya datang dari FaosModel harus
 * ditulis sendiri di boot() di bawah.
 *
 * Lihat issue3.md Bagian 4.1.
 */
class PlayerPosition extends Model
{
    use HasFactory;

    protected $table = 'player_positions';
    protected $primaryKey = 'id_player_position';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'description',
        'position_group',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'status' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {

            if (empty($model->id_player_position)) {
                $model->id_player_position = (string) Str::uuid();
            }
        });
    }

    /**
     * Player yang memakai posisi ini sebagai POSISI UTAMA.
     */
    public function primaryPlayers(): HasMany
    {
        return $this->hasMany(Player::class, 'id_primary_position', 'id_player_position');
    }

    /**
     * Player yang memakai posisi ini sebagai POSISI KEDUA.
     *
     * Relasi kedua ini bukan pelengkap -- tanpa memeriksanya, posisi yang cuma
     * dipakai sebagai posisi kedua akan lolos dihapus. Lihat Bagian 4.2.
     */
    public function secondaryPlayers(): HasMany
    {
        return $this->hasMany(Player::class, 'id_secondary_position', 'id_player_position');
    }
}
