<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SampleAnswerRequest;
use App\Http\Resources\AnswerResultResource;
use App\Models\Question;
use App\Services\SampleQuestions;
use Illuminate\Http\JsonResponse;

/** Public try-out questions for the landing page. Nothing is stored: no attempt, no answer. */
class SampleController extends Controller
{
    public function index(SampleQuestions $samples): JsonResponse
    {
        return response()->json(['data' => $samples->byLevel()]);
    }

    public function answer(SampleAnswerRequest $request, Question $question): AnswerResultResource
    {
        $result = $question->type->handler()->check($question->data, $request->validated('answer'));

        return new AnswerResultResource(['question' => $question, 'result' => $result, 'attempt_id' => null]);
    }
}
