<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttemptAnswer> */
class AttemptAnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attempt_id' => Attempt::factory(),
            'question_id' => Question::factory(),
            'answer' => ['selected' => [1]],
            'is_correct' => true,
            'time_spent_seconds' => fake()->numberBetween(5, 60),
        ];
    }

    public function incorrect(): static
    {
        return $this->state(['answer' => ['selected' => [0]], 'is_correct' => false]);
    }
}
