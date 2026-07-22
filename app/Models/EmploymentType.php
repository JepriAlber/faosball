<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function contracts(): HasMany
    {
        return $this->hasMany(EmploymentContract::class, 'id_employment_type', 'id_employment_type');
    }
}
