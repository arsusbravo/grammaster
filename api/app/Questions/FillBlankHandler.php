<?php

declare(strict_types=1);

namespace App\Questions;

final class FillBlankHandler implements QuestionHandler
{
    public function publicData(array $data): array
    {
        return [
            'text' => $data['text'],
            'blanks' => array_map(
                fn (array $blank) => ['hint' => $blank['hint'] ?? null],
                array_values($data['blanks']),
            ),
        ];
    }

    public function answerRules(array $data): array
    {
        $count = count($data['blanks']);

        return [
            'blanks' => ['required', 'array', 'size:'.$count],
            // Empty fields arrive as null (empty strings are converted by the framework).
            'blanks.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function check(array $data, array $answer): AnswerResult
    {
        $caseSensitive = (bool) ($data['case_sensitive'] ?? false);
        $ignoreWhitespace = (bool) ($data['ignore_extra_whitespace'] ?? true);

        $items = [];
        foreach (array_values($data['blanks']) as $index => $blank) {
            $given = $this->normalize((string) ($answer['blanks'][$index] ?? ''), $caseSensitive, $ignoreWhitespace);

            $items[] = $given !== '' && in_array(
                $given,
                array_map(fn (string $a) => $this->normalize($a, $caseSensitive, $ignoreWhitespace), $blank['accepted']),
                true,
            );
        }

        return new AnswerResult(! in_array(false, $items, true), $items);
    }

    public function correctAnswer(array $data): array
    {
        return [
            'blanks' => array_map(fn (array $blank) => $blank['accepted'][0], array_values($data['blanks'])),
            'accepted' => array_map(fn (array $blank) => array_values($blank['accepted']), array_values($data['blanks'])),
        ];
    }

    private function normalize(string $value, bool $caseSensitive, bool $ignoreWhitespace): string
    {
        $value = $ignoreWhitespace ? trim((string) preg_replace('/\s+/u', ' ', $value)) : $value;

        return $caseSensitive ? $value : mb_strtolower($value);
    }
}
