<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttemptMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AnswerRequest;
use App\Http\Resources\ExamAttemptResource;
use App\Http\Resources\ExamResultResource;
use App\Models\Attempt;
use App\Models\Question;
use App\Services\ExamService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AttemptController extends Controller
{
    /**
     * The attempt as it stands, so a refreshed or reopened exam can continue. A running attempt whose
     * time has run out is scored first (finished_at is then set and the client goes to the result).
     */
    public function show(Attempt $attempt, ExamService $service): ExamAttemptResource
    {
        Gate::authorize('view', $attempt);
        $this->requireExamAttempt($attempt);

        if (! $attempt->isFinished() && ExamService::isExpired($attempt->load('exam'))) {
            $attempt = $service->submit($attempt);
        }

        return new ExamAttemptResource($attempt->load(['exam.questions', 'answers']));
    }

    /** Autosave one exam answer. No verdict is returned while the exam is running. */
    public function saveAnswer(AnswerRequest $request, Attempt $attempt, Question $question, ExamService $service): Response
    {
        $this->requireExamAttempt($attempt);
        abort_if($attempt->isFinished(), 409, __('api.attempt_finished'));
        abort_if(ExamService::isExpired($attempt->load('exam')), 409, __('api.time_is_up'));
        abort_unless(
            $attempt->exam->questions()->whereKey($question->id)->exists(),
            422,
            __('api.question_not_in_exam'),
        );

        $service->saveAnswer(
            $attempt,
            $question,
            $request->validated('answer'),
            (int) $request->validated('time_spent_seconds', 0),
        );

        return response()->noContent();
    }

    /** Remove a saved answer (the student cleared it). Same rules as saving. */
    public function deleteAnswer(Attempt $attempt, Question $question): Response
    {
        Gate::authorize('update', $attempt);
        $this->requireExamAttempt($attempt);
        abort_if($attempt->isFinished(), 409, __('api.attempt_finished'));
        abort_if(ExamService::isExpired($attempt->load('exam')), 409, __('api.time_is_up'));

        $attempt->answers()->where('question_id', $question->id)->delete();

        return response()->noContent();
    }

    /** Score the exam and return the full result. Calling it again returns the same result. */
    public function submit(Attempt $attempt, ExamService $service): ExamResultResource
    {
        Gate::authorize('update', $attempt);
        $this->requireExamAttempt($attempt);

        return new ExamResultResource($service->submit($attempt)->load(['exam.questions', 'answers']));
    }

    public function result(Attempt $attempt): ExamResultResource
    {
        Gate::authorize('view', $attempt);
        $this->requireExamAttempt($attempt);
        abort_unless($attempt->isFinished(), 409, __('api.attempt_not_finished'));

        return new ExamResultResource($attempt->load(['exam.questions', 'answers']));
    }

    private function requireExamAttempt(Attempt $attempt): void
    {
        abort_unless($attempt->mode === AttemptMode::Exam, 404, __('api.not_an_exam_attempt'));
    }
}
