<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends FaosModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff';
    protected $primaryKey = 'id_staff';

    protected $fillable = [
        'id_academy', 'id_user', 'id_employment_type', 'id_staff_position',
        'staff_code', 'photo', 'full_name', 'nickname',
        'gender', 'birth_place', 'birth_date', 'nationality', 'religion', 'blood_type', 'marital_status',
        'phone', 'email', 'address', 'city', 'province', 'postal_code',
        'join_date', 'end_date', 'salary', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'join_date' => 'date',
            'end_date' => 'date',
            'salary' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class, 'id_employment_type', 'id_employment_type');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(StaffPosition::class, 'id_staff_position', 'id_staff_position');
    }

    /**
     * Accessor supaya komponen shared yang generik mengasumsikan atribut
     * `name` (mis. <x-account.dropdown>, dipakai bersama Player) tetap
     * berfungsi tanpa modifikasi -- TIDAK mengganti nama kolom `full_name`
     * jadi `name` (lihat issue11.md Bagian 4.2).
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }
}
