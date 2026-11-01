<?php

declare(strict_types=1);

use App\Enums\QuestionType;
use App\Models\Question;
use App\Support\QuestionFormMapper;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Validation\ValidationException;

/** Build the questions.data JSON from one type's editor state. */
function mapped(QuestionType $type, array $state): array
{
    return QuestionFormMapper::toData($type, [$type->value => $state]);
}

function mappingError(QuestionType $type, array $state): string
{
    try {
        mapped($type, $state);
    } catch (ValidationException $e) {
        return $e->validator->errors()->first('data');
    }

    return '';
}

it('turns every seeded question into form state and back without changing its meaning', function (): void {
    $this->seed(DatabaseSeeder::class);

    foreach (Question::all() as $question) {
        $rebuilt = QuestionFormMapper::toData($question->type, QuestionFormMapper::toForm($question->type, $question->data));
        $original = $question->data;

        $handler = $question->type->handler();
        expect($handler->correctAnswer($rebuilt))->toEqual($handler->correctAnswer($original), "correct answer #{$question->id}")
            ->and($handler->check($rebuilt, $handler->correctAnswer($original))->isCorrect)->toBeTrue("check #{$question->id}");

        // Everything that is stored survives (optional keys only differ when they were absent).
        foreach ($original as $key => $value) {
            expect($rebuilt[$key] ?? null)->toEqual($value, "{$key} of #{$question->id}");
        }
    }
});

it('fills the form with defaults for the other types', function (): void {
    $editor = QuestionFormMapper::toForm(QuestionType::MarkError, [
        'tokens' => ['Wij', 'fietst', 'naar', 'school'], 'error_index' => 1, 'correction' => 'fietsen',
    ]);

    expect($editor['mark_error']['sentence'])->toBe('Wij *fietst* naar school')
        ->and($editor['mark_error']['correction'])->toBe('fietsen')
        ->and($editor)->toHaveKeys(['multiple_choice', 'fill_blank', 'order_words', 'parse_sentence']);
});

describe('multiple_choice', function (): void {
    it('stores options and the indexes of the correct ones', function (): void {
        $data = mapped(QuestionType::MultipleChoice, ['options' => [
            ['text' => 'a', 'correct' => false],
            ['text' => ' ', 'correct' => true], // empty rows are dropped
            ['text' => 'b', 'correct' => true],
            ['text' => 'c', 'correct' => true],
        ]]);

        expect($data)->toBe(['options' => ['a', 'b', 'c'], 'correct' => [1, 2]]);
    });

    it('needs two options and a correct one', function (): void {
        expect(mappingError(QuestionType::MultipleChoice, ['options' => [['text' => 'a', 'correct' => true]]]))
            ->toBe('Geef minstens twee antwoordopties op.')
            ->and(mappingError(QuestionType::MultipleChoice, ['options' => [['text' => 'a', 'correct' => false], ['text' => 'b', 'correct' => false]]]))
            ->toBe('Kies minstens één juist antwoord.');
    });
});

describe('fill_blank', function (): void {
    it('stores accepted answers, hints and the case rules', function (): void {
        $data = mapped(QuestionType::FillBlank, [
            'text' => 'Mijn broer {{blank}} in {{blank}}.',
            'blanks' => [
                ['accepted' => ['werkt', ' '], 'hint' => 'werken'],
                ['accepted' => ['Amsterdam'], 'hint' => null],
            ],
            'case_sensitive' => true,
            'ignore_extra_whitespace' => false,
        ]);

        expect($data)->toEqual([
            'text' => 'Mijn broer {{blank}} in {{blank}}.',
            'blanks' => [['accepted' => ['werkt'], 'hint' => 'werken'], ['accepted' => ['Amsterdam']]],
            'case_sensitive' => true,
            'ignore_extra_whitespace' => false,
        ]);
    });

    it('explains mistakes', function (): void {
        $one = ['accepted' => ['x']];

        expect(mappingError(QuestionType::FillBlank, ['text' => 'geen gat', 'blanks' => [$one]]))->toContain('{{blank}}')
            ->and(mappingError(QuestionType::FillBlank, ['text' => '{{blank}} {{blank}}', 'blanks' => [$one]]))->toContain('2 keer')
            ->and(mappingError(QuestionType::FillBlank, ['text' => '{{blank}}', 'blanks' => [['accepted' => []]]]))->toContain('goed antwoord');
    });
});

describe('order_words', function (): void {
    it('splits the sentence and the alternatives into words', function (): void {
        $data = mapped(QuestionType::OrderWords, [
            'sentence' => '  Gisteren ging ik   zwemmen ',
            'alternatives' => "Ik ging gisteren zwemmen\n\n  ",
            'case_sensitive' => false,
        ]);

        expect($data['words'])->toBe(['Gisteren', 'ging', 'ik', 'zwemmen'])
            ->and($data['alternative_orders'])->toBe([['Ik', 'ging', 'gisteren', 'zwemmen']]);
    });

    it('rejects an alternative with different words and a one word sentence', function (): void {
        expect(fn () => mapped(QuestionType::OrderWords, ['sentence' => 'a b c', 'alternatives' => 'a b d']))
            ->toThrow(ValidationException::class)
            ->and(mappingError(QuestionType::OrderWords, ['sentence' => 'a']))->toContain('twee woorden');
    });
});

describe('mark_error', function (): void {
    it('finds the starred word', function (): void {
        $data = mapped(QuestionType::MarkError, ['sentence' => 'Wij *fietst* naar school', 'correction' => 'fietsen']);

        expect($data)->toEqual(['tokens' => ['Wij', 'fietst', 'naar', 'school'], 'error_index' => 1, 'correction' => 'fietsen']);
    });

    it('requires exactly one starred word and a correction', function (): void {
        expect(mappingError(QuestionType::MarkError, ['sentence' => 'Wij fietst naar school', 'correction' => 'x']))->toContain('precies één')
            ->and(mappingError(QuestionType::MarkError, ['sentence' => '*Wij* *fietst* naar', 'correction' => 'x']))->toContain('precies één')
            ->and(mappingError(QuestionType::MarkError, ['sentence' => 'Wij *fietst* naar', 'correction' => ' ']))->toContain('juiste vorm');
    });
});

describe('parse_sentence', function (): void {
    it('stores tokens with roles and the offered roles', function (): void {
        $data = mapped(QuestionType::ParseSentence, [
            'roles' => ['onderwerp', 'persoonsvorm', 'onderwerp'],
            'tokens' => [
                ['text' => 'De kat', 'role' => 'onderwerp'],
                ['text' => '', 'role' => null],
                ['text' => 'eet', 'role' => 'persoonsvorm'],
            ],
        ]);

        expect($data)->toBe([
            'tokens' => [['text' => 'De kat', 'role' => 'onderwerp'], ['text' => 'eet', 'role' => 'persoonsvorm']],
            'roles' => ['onderwerp', 'persoonsvorm'],
        ]);
    });

    it('explains mistakes', function (): void {
        $roles = ['onderwerp', 'persoonsvorm'];

        expect(mappingError(QuestionType::ParseSentence, ['roles' => $roles, 'tokens' => [['text' => 'De kat', 'role' => null], ['text' => 'eet', 'role' => 'persoonsvorm']]]))->toContain("'De kat'")
            ->and(mappingError(QuestionType::ParseSentence, ['roles' => $roles, 'tokens' => [['text' => 'De kat', 'role' => 'onderwerp']]]))->toContain('twee zinsdelen')
            ->and(mappingError(QuestionType::ParseSentence, ['roles' => $roles, 'tokens' => [['text' => 'a', 'role' => 'onderwerp'], ['text' => 'b', 'role' => 'lijdend voorwerp']]]))->toContain("'lijdend voorwerp'");
    });
});
