<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Question;
use App\Questions\AnswerResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Feedback for one answer (practice): verdict, correct answer and explanation.
 *
 * @property-read array{question: Question, result: AnswerResult, attempt_id: int|null} $resource
 */
class AnswerResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        ['question' => $question, 'result' => $result, 'attempt_id' => $attemptId] = $this->resource;

        return [
            'question_id' => $question->id,
            'attempt_id' => $attemptId,
            'is_correct' => $result->isCorrect,
            'items' => $result->items,
            'correct_answer' => $question->type->handler()->correctAnswer($question->data),
            'explanation' => $question->explanation,
        ];
    }
}
