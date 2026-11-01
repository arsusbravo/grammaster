<?php

declare(strict_types=1);

use App\Enums\QuestionType;
use App\Models\Question;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Validator;

function validAnswer(QuestionType $type, array $data, array $answer): bool
{
    return ! Validator::make(['answer' => $answer], collect($type->handler()->answerRules($data))
        ->mapWithKeys(fn ($rule, $key) => ["answer.$key" => $rule])->all())->fails();
}

describe('seeded content', function (): void {
    beforeEach(fn () => $this->seed(DatabaseSeeder::class));

    it('accepts its own correct answer for every seeded question', function (): void {
        foreach (Question::all() as $question) {
            $handler = $question->type->handler();
            $correct = $handler->correctAnswer($question->data);

            expect(validAnswer($question->type, $question->data, $correct))->toBeTrue("shape #{$question->id}")
                ->and($handler->check($question->data, $correct)->isCorrect)->toBeTrue("check #{$question->id}");
        }
    });

    it('never leaks the solution in the public data', function (): void {
        foreach (Question::all() as $question) {
            $public = $question->type->handler()->publicData($question->data);
            $json = json_encode($public, JSON_UNESCAPED_UNICODE);

            expect($public)->not->toHaveKeys(['correct', 'accepted', 'error_index', 'correction', 'alternative_orders'])
                ->and($json)->not->toContain('"role"');
        }
    });

    it('accepts every alternative order of order_words questions', function (): void {
        foreach (Question::where('type', QuestionType::OrderWords)->get() as $question) {
            foreach ($question->data['alternative_orders'] ?? [] as $order) {
                expect($question->type->handler()->check($question->data, ['order' => $order])->isCorrect)->toBeTrue();
            }
        }
    });
});

describe('multiple_choice', function (): void {
    $single = ['options' => ['a', 'b', 'c'], 'correct' => [1]];
    $multi = ['options' => ['a', 'b', 'c', 'd'], 'correct' => [0, 1]];
    $handler = QuestionType::MultipleChoice->handler();

    it('checks single and multi select regardless of order', function () use ($handler, $single, $multi): void {
        expect($handler->check($single, ['selected' => [1]])->isCorrect)->toBeTrue()
            ->and($handler->check($single, ['selected' => [0]])->isCorrect)->toBeFalse()
            ->and($handler->check($multi, ['selected' => [1, 0]])->isCorrect)->toBeTrue()
            ->and($handler->check($multi, ['selected' => [0]])->isCorrect)->toBeFalse()
            ->and($handler->check($multi, ['selected' => [0, 1, 2]])->isCorrect)->toBeFalse();
    });

    it('flags multi select in the public data', function () use ($handler, $single, $multi): void {
        expect($handler->publicData($single))->toBe(['options' => ['a', 'b', 'c'], 'multiple' => false])
            ->and($handler->publicData($multi)['multiple'])->toBeTrue();
    });

    it('validates the answer shape', function () use ($single): void {
        $t = QuestionType::MultipleChoice;
        expect(validAnswer($t, $single, ['selected' => [2]]))->toBeTrue()
            ->and(validAnswer($t, $single, ['selected' => [3]]))->toBeFalse()
            ->and(validAnswer($t, $single, ['selected' => []]))->toBeFalse()
            ->and(validAnswer($t, $single, ['selected' => [1, 1]]))->toBeFalse()
            ->and(validAnswer($t, $single, ['selected' => ['x']]))->toBeFalse()
            ->and(validAnswer($t, $single, []))->toBeFalse();
    });
});

describe('fill_blank', function (): void {
    $handler = QuestionType::FillBlank->handler();
    $data = [
        'text' => '{{blank}} jij? Ik denk dat zij hem {{blank}}.',
        'blanks' => [['accepted' => ['schrijf']], ['accepted' => ['schrijft', 'schrijft.']]],
        'case_sensitive' => false,
        'ignore_extra_whitespace' => true,
    ];

    it('ignores case, surrounding and repeated whitespace by default', function () use ($handler, $data): void {
        expect($handler->check($data, ['blanks' => ['  Schrijf ', 'SCHRIJFT']])->isCorrect)->toBeTrue()
            ->and($handler->check($data, ['blanks' => ['schrijf', 'schrijft.']])->isCorrect)->toBeTrue();
    });

    it('reports per blank results', function () use ($handler, $data): void {
        $result = $handler->check($data, ['blanks' => ['schrijf', 'schrijf']]);

        expect($result->isCorrect)->toBeFalse()->and($result->items)->toBe([true, false]);
    });

    it('treats empty and missing answers as wrong', function () use ($handler, $data): void {
        expect($handler->check($data, ['blanks' => [null, '']])->items)->toBe([false, false]);
    });

    it('can require the exact case', function () use ($handler): void {
        $data = ['text' => '{{blank}}', 'blanks' => [['accepted' => ['Amsterdam']]], 'case_sensitive' => true];

        expect($handler->check($data, ['blanks' => ['Amsterdam']])->isCorrect)->toBeTrue()
            ->and($handler->check($data, ['blanks' => ['amsterdam']])->isCorrect)->toBeFalse();
    });

    it('can require exact whitespace', function () use ($handler): void {
        $data = ['text' => '{{blank}}', 'blanks' => [['accepted' => ['a  b']]], 'ignore_extra_whitespace' => false];

        expect($handler->check($data, ['blanks' => ['a  b']])->isCorrect)->toBeTrue()
            ->and($handler->check($data, ['blanks' => ['a b']])->isCorrect)->toBeFalse();
    });

    it('requires one answer per blank', function () use ($data): void {
        expect(validAnswer(QuestionType::FillBlank, $data, ['blanks' => ['x']]))->toBeFalse()
            ->and(validAnswer(QuestionType::FillBlank, $data, ['blanks' => ['x', null]]))->toBeTrue();
    });

    it('only exposes hints, not the accepted answers', function () use ($handler): void {
        $data = ['text' => '{{blank}}', 'blanks' => [['accepted' => ['werkt'], 'hint' => 'werken']]];

        expect($handler->publicData($data))->toBe(['text' => '{{blank}}', 'blanks' => [['hint' => 'werken']]]);
    });
});

describe('order_words', function (): void {
    $handler = QuestionType::OrderWords->handler();
    $data = [
        'words' => ['Gisteren', 'ging', 'ik', 'zwemmen'],
        'alternative_orders' => [['Ik', 'ging', 'gisteren', 'zwemmen']],
        'case_sensitive' => false,
    ];

    it('accepts the canonical order and alternatives, ignoring case', function () use ($handler, $data): void {
        expect($handler->check($data, ['order' => ['Gisteren', 'ging', 'ik', 'zwemmen']])->isCorrect)->toBeTrue()
            ->and($handler->check($data, ['order' => ['ik', 'ging', 'gisteren', 'zwemmen']])->isCorrect)->toBeTrue()
            ->and($handler->check($data, ['order' => ['ging', 'ik', 'gisteren', 'zwemmen']])->isCorrect)->toBeFalse();
    });

    it('shuffles the words and never sends them in the solved order', function () use ($handler, $data): void {
        for ($i = 0; $i < 25; $i++) {
            $words = $handler->publicData($data)['words'];

            expect($words)->not->toBe($data['words'])->and($words)->toEqualCanonicalizing($data['words']);
        }
    });

    it('requires as many words as the sentence has', function () use ($data): void {
        expect(validAnswer(QuestionType::OrderWords, $data, ['order' => ['a', 'b']]))->toBeFalse()
            ->and(validAnswer(QuestionType::OrderWords, $data, ['order' => ['a', 'b', 'c', 'd']]))->toBeTrue();
    });
});

describe('mark_error', function (): void {
    $handler = QuestionType::MarkError->handler();
    $data = ['tokens' => ['Wij', 'fietst', 'naar', 'school'], 'error_index' => 1, 'correction' => 'fietsen'];

    it('checks the marked token', function () use ($handler, $data): void {
        expect($handler->check($data, ['index' => 1])->isCorrect)->toBeTrue()
            ->and($handler->check($data, ['index' => '1'])->isCorrect)->toBeTrue()
            ->and($handler->check($data, ['index' => 0])->isCorrect)->toBeFalse();
    });

    it('validates the index range and reveals the correction afterwards only', function () use ($handler, $data): void {
        expect(validAnswer(QuestionType::MarkError, $data, ['index' => 3]))->toBeTrue()
            ->and(validAnswer(QuestionType::MarkError, $data, ['index' => 4]))->toBeFalse()
            ->and(validAnswer(QuestionType::MarkError, $data, ['index' => -1]))->toBeFalse()
            ->and($handler->publicData($data))->toBe(['tokens' => ['Wij', 'fietst', 'naar', 'school']])
            ->and($handler->correctAnswer($data))->toBe(['index' => 1, 'correction' => 'fietsen']);
    });
});

describe('parse_sentence', function (): void {
    $handler = QuestionType::ParseSentence->handler();
    $data = [
        'tokens' => [
            ['text' => 'De kat', 'role' => 'onderwerp'],
            ['text' => 'eet', 'role' => 'persoonsvorm'],
            ['text' => 'de vis', 'role' => 'lijdend voorwerp'],
        ],
        'roles' => ['onderwerp', 'persoonsvorm', 'lijdend voorwerp'],
    ];

    it('checks every token and reports per token results', function () use ($handler, $data): void {
        $result = $handler->check($data, ['roles' => ['onderwerp', 'persoonsvorm', 'onderwerp']]);

        expect($result->isCorrect)->toBeFalse()->and($result->items)->toBe([true, true, false])
            ->and($handler->check($data, ['roles' => ['onderwerp', 'persoonsvorm', 'lijdend voorwerp']])->isCorrect)->toBeTrue();
    });

    it('treats an unanswered token as wrong', function () use ($handler, $data): void {
        expect($handler->check($data, ['roles' => ['onderwerp', null, null]])->items)->toBe([true, false, false]);
    });

    it('only accepts offered roles and hides the solution', function () use ($handler, $data): void {
        expect(validAnswer(QuestionType::ParseSentence, $data, ['roles' => ['onderwerp', 'persoonsvorm', 'zomaar']]))->toBeFalse()
            ->and(validAnswer(QuestionType::ParseSentence, $data, ['roles' => ['onderwerp']]))->toBeFalse()
            ->and($handler->publicData($data))->toBe([
                'tokens' => ['De kat', 'eet', 'de vis'],
                'roles' => ['onderwerp', 'persoonsvorm', 'lijdend voorwerp'],
            ]);
    });
});
