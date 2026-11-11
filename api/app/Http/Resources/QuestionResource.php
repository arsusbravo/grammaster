<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A question as a student may see it BEFORE answering: no correct answer, no explanation.
 * The type-specific `data` goes through the question type's handler to strip the solution.
 *
 * @mixin Question
 */
class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'prompt' => $this->prompt,
            'difficulty' => $this->difficulty,
            'topic' => new TopicResource($this->whenLoaded('topic')),
            'level' => new LevelResource($this->whenLoaded('level')),
            // The text the question is about (null for a normal question).
            'passage' => $this->passage_id === null ? null : new PassageResource($this->whenLoaded('passage')),
            'data' => $this->type->handler()->publicData($this->data),
            'points' => $this->whenPivotLoaded('exam_questions', fn () => $this->pivot->points),
        ];
    }
}
