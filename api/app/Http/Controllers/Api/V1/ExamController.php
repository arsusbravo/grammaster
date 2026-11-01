<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttemptMode;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamAttemptResource;
use App\Http\Resources\ExamResource;
use App\Models\Attempt;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExamController extends Controller
{
    /** Published exams, with the signed-in student's progress on each. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $exams = Exam::published()
            ->with('level')
            ->withCount('questions')
            ->withSum('questions', 'exam_questions.points')
            ->when($request->query('level'), fn ($q, $slug) => $q->whereHas('level', fn ($l) => $l->where('slug', $slug)))
            ->orderBy('level_id')
            ->orderBy('id')
            ->get();

        $attempts = Attempt::where('user_id', $request->user()->id)
            ->where('mode', AttemptMode::Exam)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get()
            ->groupBy('exam_id');

        $exams->each(function (Exam $exam) use ($attempts): void {
            $mine = $attempts->get($exam->id, collect());

            $exam->setAttribute('in_progress_attempt_id', $mine->whereNull('finished_at')->max('id'));
            $exam->setAttribute('best_score', $mine->whereNotNull('finished_at')->max('score'));
            $exam->setAttribute('last_attempt_id', $mine->whereNotNull('finished_at')->max('id'));
            $exam->setAttribute('attempts_count', $mine->whereNotNull('finished_at')->count());
        });

        return ExamResource::collection($exams);
    }

    /** Start an exam, or resume the running attempt (200) so a refresh never loses answers. */
    public function start(Request $request, Exam $exam, ExamService $service): JsonResponse
    {
        [$attempt, $created] = $service->start($request->user(), $exam);

        return (new ExamAttemptResource($attempt->load(['exam.questions', 'answers'])))
            ->response()
            ->setStatusCode($created ? 201 : 200);
    }
}
