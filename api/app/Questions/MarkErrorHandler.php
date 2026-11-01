<?php

declare(strict_types=1);

namespace App\Questions;

final class MarkErrorHandler implements QuestionHandler
{
    public function publicData(array $data): array
    {
        return ['tokens' => array_values($data['tokens'])];
    }

    public function answerRules(array $data): array
    {
        return ['index' => ['required', 'integer', 'between:0,'.(count($data['tokens']) - 1)]];
    }

    public function check(array $data, array $answer): AnswerResult
    {
        return new AnswerResult((int) $answer['index'] === (int) $data['error_index']);
    }

    public function correctAnswer(array $data): array
    {
        return ['index' => (int) $data['error_index'], 'correction' => $data['correction']];
    }
}
