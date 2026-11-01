<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttemptMode;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\User;
use App\Questions\AnswerResult;

class PracticeService
{
    /**
     * Store a practice answer. Practice answers belong to a session (an attempt); a new one is
     * started when none is given. The session score is the running percentage of correct answers
     * and finished_at tracks the last answer, so every session shows up in the history.
     *
     * @param  array<string, mixed>  $answer
     */
    public function record(User $user, Question $question, array $answer, AnswerResult $result, int $seconds, ?int $attemptId): Attempt
    {
        $attempt = $attemptId
            ? Attempt::where('user_id', $user->id)->where('mode', AttemptMode::Practice)->findOrFail($attemptId)
            : Attempt::create([
                'user_id' => $user->id,
                'mode' => AttemptMode::Practice,
                'started_at' => now(),
            ]);

        $attempt->answers()->create([
            'question_id' => $question->id,
            'answer' => $answer,
            'is_correct' => $result->isCorrect,
            'time_spent_seconds' => $seconds,
        ]);

        $total = $attempt->answers()->count();
        $correct = $attempt->answers()->where('is_correct', true)->count();

        $attempt->update([
            'finished_at' => now(),
            'score' => round($correct / $total * 100, 2),
        ]);

        return $attempt;
    }
}
