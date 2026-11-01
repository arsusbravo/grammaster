<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class QuestionIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'topic' => ['nullable', 'string', 'exists:topics,slug'],
            'level' => ['nullable', 'string', 'exists:levels,slug'],
            'difficulty' => ['nullable', 'integer', 'between:1,5'],
            'limit' => ['nullable', 'integer', 'between:1,50'],
            // Question ids to skip, so a practice session does not repeat questions.
            'exclude' => ['nullable', 'array', 'max:500'],
            'exclude.*' => ['integer'],
        ];
    }
}
