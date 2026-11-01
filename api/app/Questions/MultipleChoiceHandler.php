<?php

declare(strict_types=1);

namespace App\Questions;

final class MultipleChoiceHandler implements QuestionHandler
{
    public function publicData(array $data): array
    {
        return [
            'options' => array_values($data['options']),
            // Tells the UI whether to render checkboxes instead of radio buttons.
            'multiple' => count($data['correct']) > 1,
        ];
    }

    public function answerRules(array $data): array
    {
        $max = count($data['options']) - 1;

        return [
            'selected' => ['required', 'array', 'min:1', 'max:'.count($data['options'])],
            'selected.*' => ['integer', 'distinct', 'between:0,'.$max],
        ];
    }

    public function check(array $data, array $answer): AnswerResult
    {
        $selected = array_map('intval', $answer['selected']);
        $correct = array_map('intval', $data['correct']);
        sort($selected);
        sort($correct);

        return new AnswerResult($selected === $correct);
    }

    public function correctAnswer(array $data): array
    {
        $correct = array_map('intval', $data['correct']);
        sort($correct);

        return ['selected' => $correct];
    }
}
