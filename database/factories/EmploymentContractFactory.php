<?php

namespace Database\Factories;

use App\Models\EmploymentContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmploymentContract>
 */
class EmploymentContractFactory extends Factory
{
    protected $model = EmploymentContract::class;

    public function definition(): array
    {
        return [
            'contract_code' => strtoupper(fake()->unique()->bothify('CTR-####-C1')),
            'start_date' => now()->subMonths(6),
            'end_date' => null,
            'salary' => fake()->randomFloat(2, 3000000, 10000000),
            'status' => 'active',
            'notes' => null,
        ];
    }
}
