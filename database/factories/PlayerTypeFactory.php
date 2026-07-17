<?php

namespace Database\Factories;

use App\Models\PlayerType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerType>
 */
class PlayerTypeFactory extends Factory
{
    protected $model = PlayerType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'is_billable' => true,
            'status' => true,
        ];
    }
}
