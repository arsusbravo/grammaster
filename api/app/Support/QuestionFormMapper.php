<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\QuestionType;
use Illuminate\Validation\ValidationException;

/**
 * Translates between the admin form and the questions.data JSON.
 *
 * The form works with friendly fields per type (a sentence, a repeater of options, ...), stored in
 * an `editor` array; the API and the answer checking use the JSON shapes from QuestionData.
 */
final class QuestionFormMapper
{
    public const DEFAULT_ROLES = [
        'onderwerp',
        'persoonsvorm',
        'lijdend voorwerp',
        'meewerkend voorwerp',
        'bijwoordelijke bepaling',
    ];

    public const SUGGESTED_ROLES = [
        ...self::DEFAULT_ROLES,
        'naamwoordelijk deel van het gezegde',
        'werkwoordelijk deel van het gezegde',
    ];

    /** Empty editor state for every type (so switching the type in the form has something to show). */
    public static function defaults(): array
    {
        return [
            'multiple_choice' => [
                'options' => [['text' => '', 'correct' => false], ['text' => '', 'correct' => false]],
            ],
            'fill_blank' => [
                'text' => '',
                'blanks' => [['accepted' => [], 'hint' => null]],
                'case_sensitive' => false,
                'ignore_extra_whitespace' => true,
            ],
            'order_words' => ['sentence' => '', 'alternatives' => '', 'case_sensitive' => false],
            'mark_error' => ['sentence' => '', 'correction' => ''],
            'parse_sentence' => [
                'roles' => self::DEFAULT_ROLES,
                'tokens' => [['text' => '', 'role' => null], ['text' => '', 'role' => null]],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data  Stored questions.data
     * @return array<string, mixed> Editor state with `$type` filled from the data
     */
    public static function toForm(QuestionType $type, array $data): array
    {
        $editor = self::defaults();

        $editor[$type->value] = match ($type) {
            QuestionType::MultipleChoice => [
                'options' => array_map(
                    fn (string $text, int $i) => ['text' => $text, 'correct' => in_array($i, $data['correct'], true)],
                    array_values($data['options']),
                    array_keys(array_values($data['options'])),
                ),
            ],
            QuestionType::FillBlank => [
                'text' => $data['text'],
                'blanks' => array_map(
                    fn (array $blank) => ['accepted' => array_values($blank['accepted']), 'hint' => $blank['hint'] ?? null],
                    array_values($data['blanks']),
                ),
                'case_sensitive' => (bool) ($data['case_sensitive'] ?? false),
                'ignore_extra_whitespace' => (bool) ($data['ignore_extra_whitespace'] ?? true),
            ],
            QuestionType::OrderWords => [
                'sentence' => implode(' ', $data['words']),
                'alternatives' => implode("\n", array_map(fn (array $o) => implode(' ', $o), $data['alternative_orders'] ?? [])),
                'case_sensitive' => (bool) ($data['case_sensitive'] ?? false),
            ],
            QuestionType::MarkError => [
                'sentence' => implode(' ', array_map(
                    fn (string $token, int $i) => $i === $data['error_index'] ? "*{$token}*" : $token,
                    array_values($data['tokens']),
                    array_keys(array_values($data['tokens'])),
                )),
                'correction' => $data['correction'],
            ],
            QuestionType::ParseSentence => [
                'roles' => array_values($data['roles']),
                'tokens' => array_map(
                    fn (array $t) => ['text' => $t['text'], 'role' => $t['role']],
                    array_values($data['tokens']),
                ),
            ],
        };

        return $editor;
    }

    /**
     * Build the validated questions.data JSON from the editor state of one type.
     *
     * @param  array<string, mixed>  $editor
     * @return array<string, mixed>
     *
     * @throws ValidationException With Dutch messages under the `data` key.
     */
    public static function toData(QuestionType $type, array $editor): array
    {
        $state = $editor[$type->value] ?? [];

        $data = match ($type) {
            QuestionType::MultipleChoice => self::multipleChoice($state),
            QuestionType::FillBlank => self::fillBlank($state),
            QuestionType::OrderWords => self::orderWords($state),
            QuestionType::MarkError => self::markError($state),
            QuestionType::ParseSentence => self::parseSentence($state),
        };

        return QuestionData::validate($type, $data);
    }

    /** @param  array<string, mixed>  $state */
    private static function multipleChoice(array $state): array
    {
        $options = [];
        $correct = [];

        foreach (array_values($state['options'] ?? []) as $option) {
            $text = trim((string) ($option['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            if (! empty($option['correct'])) {
                $correct[] = count($options);
            }
            $options[] = $text;
        }

        if (count($options) < 2) {
            self::fail('Geef minstens twee antwoordopties op.');
        }
        if ($correct === []) {
            self::fail('Kies minstens één juist antwoord.');
        }

        return ['options' => $options, 'correct' => $correct];
    }

    /** @param  array<string, mixed>  $state */
    private static function fillBlank(array $state): array
    {
        $text = trim((string) ($state['text'] ?? ''));
        $blanks = [];

        foreach (array_values($state['blanks'] ?? []) as $blank) {
            $accepted = array_values(array_filter(
                array_map(fn ($a) => trim((string) $a), $blank['accepted'] ?? []),
                fn (string $a) => $a !== '',
            ));
            if ($accepted === []) {
                self::fail('Elk invulveld heeft minstens één goed antwoord nodig.');
            }

            $entry = ['accepted' => $accepted];
            if (filled($blank['hint'] ?? null)) {
                $entry['hint'] = trim((string) $blank['hint']);
            }
            $blanks[] = $entry;
        }

        $markers = substr_count($text, QuestionData::BLANK_MARKER);
        if ($markers === 0) {
            self::fail('Zet '.QuestionData::BLANK_MARKER.' in de tekst op de plek van het invulveld.');
        }
        if ($markers !== count($blanks)) {
            self::fail("De tekst bevat {$markers} keer ".QuestionData::BLANK_MARKER.' maar je hebt '.count($blanks).' invulvelden ingevuld.');
        }

        return [
            'text' => $text,
            'blanks' => $blanks,
            'case_sensitive' => (bool) ($state['case_sensitive'] ?? false),
            'ignore_extra_whitespace' => (bool) ($state['ignore_extra_whitespace'] ?? true),
        ];
    }

    /** @param  array<string, mixed>  $state */
    private static function orderWords(array $state): array
    {
        $words = self::words((string) ($state['sentence'] ?? ''));
        if (count($words) < 2) {
            self::fail('Typ een zin van minstens twee woorden.');
        }

        $alternatives = [];
        foreach (preg_split('/\R/u', (string) ($state['alternatives'] ?? '')) ?: [] as $line) {
            $order = self::words($line);
            if ($order !== []) {
                $alternatives[] = $order;
            }
        }

        return [
            'words' => $words,
            'alternative_orders' => $alternatives,
            'case_sensitive' => (bool) ($state['case_sensitive'] ?? false),
        ];
    }

    /** @param  array<string, mixed>  $state */
    private static function markError(array $state): array
    {
        $tokens = self::words((string) ($state['sentence'] ?? ''));
        if (count($tokens) < 2) {
            self::fail('Typ een zin van minstens twee woorden.');
        }

        $marked = [];
        foreach ($tokens as $index => $token) {
            if (strlen($token) > 2 && str_starts_with($token, '*') && str_ends_with($token, '*')) {
                $marked[] = $index;
                $tokens[$index] = substr($token, 1, -1);
            }
        }

        if (count($marked) !== 1) {
            self::fail('Zet sterretjes om precies één fout woord, bijvoorbeeld: Wij *fietst* naar school.');
        }

        $correction = trim((string) ($state['correction'] ?? ''));
        if ($correction === '') {
            self::fail('Vul de juiste vorm van het foute woord in.');
        }

        return ['tokens' => $tokens, 'error_index' => $marked[0], 'correction' => $correction];
    }

    /** @param  array<string, mixed>  $state */
    private static function parseSentence(array $state): array
    {
        $roles = array_values(array_unique(array_filter(
            array_map(fn ($r) => trim((string) $r), $state['roles'] ?? []),
            fn (string $r) => $r !== '',
        )));

        $tokens = [];
        foreach (array_values($state['tokens'] ?? []) as $token) {
            $text = trim((string) ($token['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            if (blank($token['role'] ?? null)) {
                self::fail("Kies een functie voor het zinsdeel '{$text}'.");
            }
            $tokens[] = ['text' => $text, 'role' => $token['role']];
        }

        if (count($tokens) < 2) {
            self::fail('Geef minstens twee zinsdelen op.');
        }
        if (count($roles) < 2) {
            self::fail('Geef minstens twee functies op waaruit de leerling kan kiezen.');
        }

        return ['tokens' => $tokens, 'roles' => $roles];
    }

    /** @return list<string> */
    private static function words(string $sentence): array
    {
        return preg_split('/\s+/u', trim($sentence), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['data' => [$message]]);
    }
}
