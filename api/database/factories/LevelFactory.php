<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Level> */
class LevelFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'slug' => $name.'-'.fake()->unique()->numberBetween(1, 99999),
            'name' => ucfirst($name),
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
