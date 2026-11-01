<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates an answer for the question in the route ({question}) using that question type's rules.
 * Used by both the practice check and the exam autosave.
 */
class AnswerRequest extends FormRequest
{
    /** On the exam autosave route the attempt must belong to the student. */
    public function authorize(): bool
    {
        $attempt = $this->route('attempt');

        return $attempt === null || (bool) $this->user()?->can('update', $attempt);
    }

    public function rules(): array
    {
        /** @var Question $question */
        $question = $this->route('question');

        $rules = [
            'answer' => ['required', 'array'],
            'time_spent_seconds' => ['nullable', 'integer', 'between:0,86400'],
            // Practice only: continue an existing practice session.
            'attempt_id' => [
                'nullable',
                'integer',
                Rule::exists('attempts', 'id')->where('user_id', $this->user()?->id)->where('mode', 'practice'),
            ],
        ];

        foreach ($question->type->handler()->answerRules($question->data) as $key => $rule) {
            $rules["answer.$key"] = $rule;
        }

        return $rules;
    }
}
