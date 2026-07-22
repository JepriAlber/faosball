<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffPosition extends FaosModel
{
    use HasFactory;

    protected $table = 'staff_positions';
    protected $primaryKey = 'id_staff_position';

    protected $fillable = ['id_academy', 'role_id', 'code', 'name', 'is_coach', 'description', 'status'];

    protected function casts(): array
    {
        return [
            'is_coach' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    /**
     * "Default Role" -- role_id merujuk ke roles.id (bigint), BUKAN kolom
     * uuid manapun. belongsTo() default owner key 'id' sudah otomatis
     * benar untuk kasus ini (tidak perlu parameter ketiga).
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'id_staff_position', 'id_staff_position');
    }
}
