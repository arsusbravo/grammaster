<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AnswerRequest;
use App\Http\Requests\Api\QuestionIndexRequest;
use App\Http\Resources\AnswerResultResource;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Services\PracticeService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuestionController extends Controller
{
    /** Random published questions for practice. Answers and explanations are never included. */
    public function index(QuestionIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $questions = Question::published()
            ->with(['topic', 'level', 'passage'])
            ->when($filters['topic'] ?? null, fn ($q, $slug) => $q->whereHas('topic', fn ($t) => $t->where('slug', $slug)))
            ->when($filters['level'] ?? null, fn ($q, $slug) => $q->whereHas('level', fn ($l) => $l->where('slug', $slug)))
            ->when($filters['difficulty'] ?? null, fn ($q, $difficulty) => $q->where('difficulty', $difficulty))
            ->when($filters['exclude'] ?? null, fn ($q, $ids) => $q->whereNotIn('id', $ids))
            ->inRandomOrder()
            ->limit($filters['limit'] ?? 10)
            ->get();

        return QuestionResource::collection($questions);
    }

    /** Check a practice answer on the server and return the verdict, correct answer and explanation. */
    public function answer(AnswerRequest $request, Question $question, PracticeService $practice): AnswerResultResource
    {
        $answer = $request->validated('answer');
        $result = $question->type->handler()->check($question->data, $answer);

        $attempt = $practice->record(
            $request->user(),
            $question,
            $answer,
            $result,
            (int) $request->validated('time_spent_seconds', 0),
            $request->validated('attempt_id'),
        );

        return new AnswerResultResource([
            'question' => $question,
            'result' => $result,
            'attempt_id' => $attempt->id,
        ]);
    }
}
