<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\QuestionType;
use App\Models\Attempt;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Result of a finished exam with a full review: the student's answer, the correct answer
 * and the explanation for every question.
 *
 * @mixin Attempt
 */
class ExamResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $answers = $this->answers->keyBy('question_id');

        $review = $this->exam->questions->load('topic')->map(function ($question) use ($answers) {
            $given = $answers->get($question->id);
            $points = $question->pivot->points;

            return [
                'question' => new QuestionResource($question),
                'answer' => $given?->answer,
                'is_correct' => (bool) $given?->is_correct,
                // Per blank / per token verdicts (null for types with a single answer).
                'items' => $given
                    ? $question->type->handler()->check($question->data, $given->answer)->items
                    : $this->unansweredItems($question),
                'points' => $points,
                'points_earned' => $given?->is_correct ? $points : 0,
                'correct_answer' => $question->type->handler()->correctAnswer($question->data),
                'explanation' => $question->explanation,
            ];
        });

        $total = (int) $review->sum('points');
        $earned = (int) $review->sum('points_earned');

        return [
            'id' => $this->id,
            'exam' => [
                'id' => $this->exam->id,
                'title' => $this->exam->title,
                'pass_mark_percent' => $this->exam->pass_mark_percent,
            ],
            'score' => (float) $this->score,
            'points_earned' => $earned,
            'points_total' => $total,
            'passed' => (float) $this->score >= $this->exam->pass_mark_percent,
            'started_at' => $this->started_at->toIso8601String(),
            'finished_at' => $this->finished_at->toIso8601String(),
            'duration_seconds' => (int) round($this->started_at->diffInSeconds($this->finished_at)),
            'review' => $review->values(),
        ];
    }

    /** @return list<bool>|null All parts wrong for a question that was left empty. */
    private function unansweredItems(Question $question): ?array
    {
        return match ($question->type) {
            QuestionType::FillBlank => array_fill(0, count($question->data['blanks']), false),
            QuestionType::ParseSentence => array_fill(0, count($question->data['tokens']), false),
            default => null,
        };
    }
}
