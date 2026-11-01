<?php

declare(strict_types=1);

use App\Enums\QuestionType;
use App\Models\Question;
use App\Support\QuestionData;
use Illuminate\Validation\ValidationException;

it('accepts the factory data for every question type', function (QuestionType $type, string $state): void {
    $data = Question::factory()->{$state}()->make()->data;

    expect(QuestionData::validate($type, $data))->toBeArray();
})->with([
    [QuestionType::MultipleChoice, 'multipleChoice'],
    [QuestionType::FillBlank, 'fillBlank'],
    [QuestionType::OrderWords, 'orderWords'],
    [QuestionType::MarkError, 'markError'],
    [QuestionType::ParseSentence, 'parseSentence'],
]);

it('rejects invalid data', function (QuestionType $type, array $data): void {
    QuestionData::validate($type, $data);
})->throws(ValidationException::class)->with([
    'correct index outside options' => [QuestionType::MultipleChoice, ['options' => ['a', 'b'], 'correct' => [2]]],
    'no correct answer' => [QuestionType::MultipleChoice, ['options' => ['a', 'b'], 'correct' => []]],
    'blank count mismatch' => [QuestionType::FillBlank, ['text' => '{{blank}} en {{blank}}', 'blanks' => [['accepted' => ['x']]]]],
    'no blank marker' => [QuestionType::FillBlank, ['text' => 'geen gat', 'blanks' => [['accepted' => ['x']]]]],
    'alternative uses other words' => [QuestionType::OrderWords, ['words' => ['a', 'b'], 'alternative_orders' => [['a', 'c']]]],
    'error index outside tokens' => [QuestionType::MarkError, ['tokens' => ['a', 'b'], 'error_index' => 5, 'correction' => 'x']],
    'unknown role' => [QuestionType::ParseSentence, [
        'tokens' => [['text' => 'a', 'role' => 'onderwerp'], ['text' => 'b', 'role' => 'rare rol']],
        'roles' => ['onderwerp', 'persoonsvorm'],
    ]],
]);
