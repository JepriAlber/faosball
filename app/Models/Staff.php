<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends FaosModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff';
    protected $primaryKey = 'id_staff';

    protected $fillable = [
        'id_academy', 'id_user',
        'staff_code', 'photo', 'full_name', 'nickname',
        'gender', 'birth_place', 'birth_date', 'nationality', 'religion', 'blood_type', 'marital_status',
        'phone', 'email', 'address', 'city', 'province', 'postal_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
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

    /**
     * Seluruh histori kontrak staff ini, terbaru duluan.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(EmploymentContract::class, 'id_staff', 'id_staff')->latest();
    }

    /**
     * Kontrak yang SEDANG berlaku. Maksimal 1 baris -- dijamin
     * EmploymentContractService (lihat Tahap 5), bukan constraint DB
     * (lihat issue12.md Bagian 2d).
     */
    public function activeContract(): HasOne
    {
        return $this->hasOne(EmploymentContract::class, 'id_staff', 'id_staff')->where('status', 'active');
    }

    /**
     * Kontrak yang sudah dibuat tapi belum berlaku (kalau ada).
     * Maksimal 1 baris, dijamin Service yang sama.
     */
    public function draftContract(): HasOne
    {
        return $this->hasOne(EmploymentContract::class, 'id_staff', 'id_staff')->where('status', 'draft');
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
