<?php

namespace Database\Factories;

use App\Models\TeamStaffPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeamStaffPosition> */
class TeamStaffPositionFactory extends Factory
{
    protected $model = TeamStaffPosition::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'name' => fake()->unique()->jobTitle(),
            'description' => null,
            'status' => true,
        ];
    }
}
