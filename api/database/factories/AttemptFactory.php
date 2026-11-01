<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AttemptMode;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Attempt> */
class AttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mode' => AttemptMode::Practice,
            'exam_id' => null,
            'started_at' => now()->subMinutes(10),
            'finished_at' => null,
            'score' => null,
        ];
    }

    public function exam(?Exam $exam = null): static
    {
        return $this->state(fn () => [
            'mode' => AttemptMode::Exam,
            'exam_id' => $exam?->id ?? Exam::factory(),
        ]);
    }

    public function finished(float $score = 70.0): static
    {
        return $this->state(fn () => [
            'finished_at' => now(),
            'score' => $score,
        ]);
    }
}
