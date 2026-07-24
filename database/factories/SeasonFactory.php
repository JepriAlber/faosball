<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Season> */
class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        return [
            'name' => (string) now()->year,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => true,
        ];
    }
}
