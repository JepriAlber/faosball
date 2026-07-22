<?php

namespace Database\Factories;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'staff_code' => strtoupper(fake()->unique()->bothify('STF-####')),
            'full_name' => fake()->name(),
            'nickname' => fake()->firstName(),
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->date(),
            'nationality' => 'Indonesia',
            'phone' => fake()->phoneNumber(),
        ];
    }
}
