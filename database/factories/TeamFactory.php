<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Team> */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('TM###')),
            'name' => fake()->words(2, true),
            'team_type' => 'regular',
            'description' => null,
            'status' => true,
        ];
    }
}
