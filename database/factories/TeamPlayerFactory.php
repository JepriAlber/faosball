<?php

namespace Database\Factories;

use App\Models\TeamPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeamPlayer> */
class TeamPlayerFactory extends Factory
{
    protected $model = TeamPlayer::class;

    public function definition(): array
    {
        return [
            'jersey_number' => fake()->unique()->numberBetween(1, 99),
            'is_captain' => false,
            'join_date' => now(),
            'leave_date' => null,
            'notes' => null,
        ];
    }
}
