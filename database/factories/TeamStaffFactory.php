<?php

namespace Database\Factories;

use App\Models\TeamStaff;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeamStaff> */
class TeamStaffFactory extends Factory
{
    protected $model = TeamStaff::class;

    public function definition(): array
    {
        return [
            'join_date' => now(),
            'leave_date' => null,
            'notes' => null,
        ];
    }
}
