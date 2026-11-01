<?php

declare(strict_types=1);

namespace App\Questions;

final class OrderWordsHandler implements QuestionHandler
{
    public function publicData(array $data): array
    {
        $words = array_values($data['words']);
        $shuffled = $words;

        // The stored order is the solution, so never send it as-is.
        for ($attempt = 0; $attempt < 10 && $shuffled === $words; $attempt++) {
            shuffle($shuffled);
        }

        return ['words' => $shuffled];
    }

    public function answerRules(array $data): array
    {
        return [
            'order' => ['required', 'array', 'size:'.count($data['words'])],
            'order.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function check(array $data, array $answer): AnswerResult
    {
        $caseSensitive = (bool) ($data['case_sensitive'] ?? false);
        $given = $this->normalize($answer['order'], $caseSensitive);

        $accepted = [$data['words'], ...($data['alternative_orders'] ?? [])];
        foreach ($accepted as $order) {
            if ($this->normalize($order, $caseSensitive) === $given) {
                return new AnswerResult(true);
            }
        }

        return new AnswerResult(false);
    }

    public function correctAnswer(array $data): array
    {
        return [
            'order' => array_values($data['words']),
            'alternative_orders' => array_values($data['alternative_orders'] ?? []),
        ];
    }

    /**
     * @param  array<int, string>  $words
     * @return list<string>
     */
    private function normalize(array $words, bool $caseSensitive): array
    {
        return array_map(
            fn (string $w) => $caseSensitive ? trim($w) : mb_strtolower(trim($w)),
            array_values($words),
        );
    }
}
