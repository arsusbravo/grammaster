<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Attempt;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A running exam: questions without answers or feedback, plus the answers saved so far,
 * so a page refresh never loses work.
 *
 * @mixin Attempt
 */
class ExamAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $endsAt = ExamService::endsAt($this->resource);

        return [
            'id' => $this->id,
            'exam' => [
                'id' => $this->exam->id,
                'title' => $this->exam->title,
                'time_limit_minutes' => $this->exam->time_limit_minutes,
                'pass_mark_percent' => $this->exam->pass_mark_percent,
            ],
            'started_at' => $this->started_at->toIso8601String(),
            'ends_at' => $endsAt->toIso8601String(),
            // Rounded up so the timer never shows less time than the student really has left.
            'remaining_seconds' => max(0, (int) ceil(now()->diffInSeconds($endsAt, false))),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'questions' => QuestionResource::collection($this->exam->questions->load(['topic', 'passage'])),
            // Object keyed by question id (always an object, even when empty).
            'answers' => (object) $this->answers->mapWithKeys(fn ($a) => [$a->question_id => $a->answer])->all(),
        ];
    }
}
