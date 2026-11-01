<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\Question;
use App\Services\SampleQuestions;
use Illuminate\Foundation\Http\FormRequest;

/** An answer to a landing page sample question: only the answer itself, validated with the question type's rules. */
class SampleAnswerRequest extends FormRequest
{
    /** Anything that is not a sample behaves as if it does not exist (before any validation runs). */
    public function authorize(): bool
    {
        abort_unless(app(SampleQuestions::class)->isSample($this->route('question')), 404);

        return true;
    }

    public function rules(): array
    {
        /** @var Question $question */
        $question = $this->route('question');
        $rules = ['answer' => ['required', 'array']];

        foreach ($question->type->handler()->answerRules($question->data) as $key => $rule) {
            $rules["answer.$key"] = $rule;
        }

        return $rules;
    }
}
