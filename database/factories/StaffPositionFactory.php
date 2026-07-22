<?php

namespace Database\Factories;

use App\Models\StaffPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffPosition>
 */
class StaffPositionFactory extends Factory
{
    protected $model = StaffPosition::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->unique()->jobTitle(),
            'is_coach' => false,
            'description' => fake()->sentence(),
            'status' => true,
        ];
    }
}
