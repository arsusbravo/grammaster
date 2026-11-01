<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Exam> */
class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Oefenexamen '.fake()->unique()->numberBetween(1, 9999),
            'level_id' => Level::factory(),
            'time_limit_minutes' => 30,
            'pass_mark_percent' => 55,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }
}
