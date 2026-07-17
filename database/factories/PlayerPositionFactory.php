<?php

namespace Database\Factories;

use App\Models\PlayerPosition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PlayerPosition>
 */
class PlayerPositionFactory extends Factory
{
    protected $model = PlayerPosition::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(3)),
            'name' => 'Position ' . fake()->unique()->word(),
            'description' => fake()->sentence(),
            'position_group' => fake()->randomElement(['Goalkeeper', 'Defender', 'Midfielder', 'Forward']),
            'sort_order' => fake()->numberBetween(1, 99),
            'status' => true,
        ];
    }
}
