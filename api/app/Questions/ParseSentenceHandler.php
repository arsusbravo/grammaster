<?php

declare(strict_types=1);

namespace App\Questions;

use Illuminate\Validation\Rule;

final class ParseSentenceHandler implements QuestionHandler
{
    public function publicData(array $data): array
    {
        return [
            'tokens' => array_map(fn (array $token) => $token['text'], array_values($data['tokens'])),
            'roles' => array_values($data['roles']),
        ];
    }

    public function answerRules(array $data): array
    {
        return [
            'roles' => ['required', 'array', 'size:'.count($data['tokens'])],
            'roles.*' => ['nullable', 'string', Rule::in($data['roles'])],
        ];
    }

    public function check(array $data, array $answer): AnswerResult
    {
        $items = [];
        foreach (array_values($data['tokens']) as $index => $token) {
            $items[] = ($answer['roles'][$index] ?? null) === $token['role'];
        }

        return new AnswerResult(! in_array(false, $items, true), $items);
    }

    public function correctAnswer(array $data): array
    {
        return ['roles' => array_map(fn (array $token) => $token['role'], array_values($data['tokens']))];
    }
}
