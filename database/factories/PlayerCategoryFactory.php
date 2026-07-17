<?php

namespace Database\Factories;

use App\Models\PlayerCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerCategory>
 */
class PlayerCategoryFactory extends Factory
{
    protected $model = PlayerCategory::class;

    public function definition(): array
    {
        return [
            'name' => 'U-' . fake()->unique()->numberBetween(8, 21),
            'description' => fake()->sentence(),
            'min_age' => 10,
            'max_age' => 12,
            'status' => true,
        ];
    }
}
