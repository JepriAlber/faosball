<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentContract extends FaosModel
{
    use HasFactory;

    protected $table = 'employment_contracts';
    protected $primaryKey = 'id_employment_contract';

    protected $fillable = [
        'id_academy', 'id_staff', 'id_employment_type', 'id_staff_position',
        'contract_code', 'start_date', 'end_date', 'salary', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'salary' => 'decimal:2',
        ];
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy', 'id_academy');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'id_staff', 'id_staff');
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class, 'id_employment_type', 'id_employment_type');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(StaffPosition::class, 'id_staff_position', 'id_staff_position');
    }
}
