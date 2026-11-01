<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Exam */
class ExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'level' => new LevelResource($this->whenLoaded('level')),
            'time_limit_minutes' => $this->time_limit_minutes,
            'pass_mark_percent' => $this->pass_mark_percent,
            'question_count' => $this->whenCounted('questions'),
            'total_points' => $this->when(
                $this->resource->offsetExists('questions_sum_exam_questionspoints'),
                fn () => (int) $this->resource->getAttribute('questions_sum_exam_questionspoints'),
            ),
            // Set by the controller for the signed-in student (null when there is nothing yet).
            'in_progress_attempt_id' => $this->resource->getAttribute('in_progress_attempt_id'),
            'best_score' => $this->resource->getAttribute('best_score'),
            'last_attempt_id' => $this->resource->getAttribute('last_attempt_id'),
            'attempts_count' => (int) $this->resource->getAttribute('attempts_count'),
        ];
    }
}
