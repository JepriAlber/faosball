<?php

namespace Database\Factories;

use App\Models\Academy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Academy>
 */
class AcademyFactory extends Factory
{
    protected $model = Academy::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'code' => strtoupper(Str::random(4)),
            'slug' => Str::slug($name),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '081234567890',
            'address' => fake()->address(),
            'status' => true,
        ];
    }
}
