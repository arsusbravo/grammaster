<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\QuestionType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Validates the `questions.data` JSON for each question type.
 *
 * Shapes (stored in JSON so new types need no migration):
 *  - multiple_choice: options[], correct[] (indexes into options; several = multi-select)
 *  - fill_blank: text with {{blank}} markers, blanks[]{accepted[], hint?}, case_sensitive?, ignore_extra_whitespace?
 *  - order_words: words[] in the correct order (the API shuffles), alternative_orders[][]?, case_sensitive?
 *  - mark_error: tokens[], error_index, correction
 *  - parse_sentence: tokens[]{text, role}, roles[] (the options offered to the student)
 */
final class QuestionData
{
    public const BLANK_MARKER = '{{blank}}';

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public static function validate(QuestionType $type, array $data): array
    {
        $validator = Validator::make($data, $type->dataRules());

        $validator->after(function ($validator) use ($type, $data): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach (self::crossFieldErrors($type, $data) as $message) {
                $validator->errors()->add('data', $message);
            }
        });

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private static function crossFieldErrors(QuestionType $type, array $data): array
    {
        $errors = [];

        switch ($type) {
            case QuestionType::MultipleChoice:
                $max = count($data['options']) - 1;
                foreach ($data['correct'] as $index) {
                    if ($index > $max) {
                        $errors[] = 'Een juist antwoord verwijst naar een optie die niet bestaat.';
                    }
                }
                break;

            case QuestionType::FillBlank:
                $markers = substr_count($data['text'], self::BLANK_MARKER);
                if ($markers < 1 || $markers !== count($data['blanks'])) {
                    $errors[] = 'Het aantal '.self::BLANK_MARKER.'-markeringen in de tekst moet gelijk zijn aan het aantal invulvelden.';
                }
                break;

            case QuestionType::OrderWords:
                $canonical = self::normalizeWords($data['words'], $data['case_sensitive'] ?? false);
                foreach ($data['alternative_orders'] ?? [] as $order) {
                    if (self::normalizeWords($order, $data['case_sensitive'] ?? false) === $canonical) {
                        continue;
                    }
                    $a = self::normalizeWords($order, $data['case_sensitive'] ?? false);
                    $b = $canonical;
                    sort($a);
                    sort($b);
                    if ($a !== $b) {
                        $errors[] = 'Elke andere goede volgorde moet precies dezelfde woorden bevatten.';
                    }
                }
                break;

            case QuestionType::MarkError:
                if ($data['error_index'] > count($data['tokens']) - 1) {
                    $errors[] = 'Het foute woord ligt buiten de zin.';
                }
                break;

            case QuestionType::ParseSentence:
                foreach ($data['tokens'] as $token) {
                    if (! in_array($token['role'], $data['roles'], true)) {
                        $errors[] = "De functie '{$token['role']}' staat niet in de lijst met functies.";
                    }
                }
                break;
        }

        return $errors;
    }

    /**
     * @param  list<string>  $words
     * @return list<string>
     */
    private static function normalizeWords(array $words, bool $caseSensitive): array
    {
        return array_map(fn (string $w) => $caseSensitive ? $w : mb_strtolower($w), array_values($words));
    }
}
