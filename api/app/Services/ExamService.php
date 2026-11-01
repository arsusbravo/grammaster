<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttemptMode;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ExamService
{
    /** Extra seconds after the deadline in which an autosave is still accepted (network delay). */
    public const GRACE_SECONDS = 15;

    public static function endsAt(Attempt $attempt): CarbonImmutable
    {
        return CarbonImmutable::instance($attempt->started_at)->addMinutes($attempt->exam->time_limit_minutes);
    }

    public static function isExpired(Attempt $attempt): bool
    {
        return now()->greaterThan(self::endsAt($attempt)->addSeconds(self::GRACE_SECONDS));
    }

    /**
     * Start an exam, or resume the running one so a refresh never loses work.
     * A running attempt whose time ran out is scored first, then a fresh attempt starts.
     *
     * @return array{0: Attempt, 1: bool} The attempt and whether it was newly created.
     */
    public function start(User $user, Exam $exam): array
    {
        $running = Attempt::where('user_id', $user->id)
            ->where('mode', AttemptMode::Exam)
            ->where('exam_id', $exam->id)
            ->whereNull('finished_at')
            ->latest('id')
            ->first();

        if ($running && ! self::isExpired($running->load('exam'))) {
            return [$running, false];
        }

        if ($running) {
            $this->submit($running);
        }

        $attempt = Attempt::create([
            'user_id' => $user->id,
            'mode' => AttemptMode::Exam,
            'exam_id' => $exam->id,
            'started_at' => now(),
        ]);

        return [$attempt, true];
    }

    /**
     * Save (or replace) an answer while the exam runs. Nothing is scored or revealed yet.
     *
     * @param  array<string, mixed>  $answer
     */
    public function saveAnswer(Attempt $attempt, Question $question, array $answer, int $seconds): void
    {
        $attempt->answers()->updateOrCreate(
            ['question_id' => $question->id],
            ['answer' => $answer, 'is_correct' => null, 'time_spent_seconds' => $seconds],
        );
    }

    /** Score the exam. Safe to call twice: a finished attempt is returned untouched. */
    public function submit(Attempt $attempt): Attempt
    {
        return DB::transaction(function () use ($attempt): Attempt {
            $attempt = Attempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($attempt->isFinished()) {
                return $attempt;
            }

            $questions = $attempt->exam->questions;
            $answers = $attempt->answers()->get()->keyBy('question_id');

            $total = 0;
            $earned = 0;

            foreach ($questions as $question) {
                $points = $question->pivot->points;
                $total += $points;
                $given = $answers->get($question->id);

                if (! $given) {
                    continue;
                }

                $result = $question->type->handler()->check($question->data, $given->answer);
                $given->update(['is_correct' => $result->isCorrect]);
                $earned += $result->isCorrect ? $points : 0;
            }

            $attempt->update([
                'finished_at' => now(),
                'score' => $total > 0 ? round($earned / $total * 100, 2) : 0,
            ]);

            return $attempt;
        });
    }
}
