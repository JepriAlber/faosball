<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentType extends FaosModel
{
    use HasFactory;

    protected $table = 'employment_types';
    protected $primaryKey = 'id_employment_type';

    protected $fillable = ['id_academy', 'name', 'description', 'status'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    // relasi staff() BELUM ada di sini -- ditambahkan issue11.md Tahap 9
    // setelah tabel `staff` dibuat (lihat Aturan Emas brief ini).
}
