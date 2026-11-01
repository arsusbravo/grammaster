<?php

declare(strict_types=1);

use App\Enums\AttemptMode;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\User;

it('requires authentication', function (): void {
    $question = Question::factory()->create();

    $this->postJson("/api/v1/questions/{$question->id}/answer", ['answer' => ['selected' => [1]]])->assertUnauthorized();
});

it('checks every question type on the server', function (string $state, array $right, array $wrong): void {
    student();
    $question = Question::factory()->{$state}()->create(['explanation' => 'Dit is de uitleg.']);
    $url = "/api/v1/questions/{$question->id}/answer";

    $this->postJson($url, ['answer' => $right])
        ->assertOk()
        ->assertJsonPath('data.is_correct', true)
        ->assertJsonPath('data.question_id', $question->id)
        ->assertJsonPath('data.explanation', 'Dit is de uitleg.')
        ->assertJsonStructure(['data' => ['attempt_id', 'correct_answer', 'items']]);

    $this->postJson($url, ['answer' => $wrong])
        ->assertOk()
        ->assertJsonPath('data.is_correct', false)
        ->assertJsonPath('data.explanation', 'Dit is de uitleg.');
})->with([
    'multiple_choice' => ['multipleChoice', ['selected' => [1]], ['selected' => [0]]],
    'fill_blank' => ['fillBlank', ['blanks' => [' Werkt ']], ['blanks' => ['werk']]],
    'order_words' => ['orderWords', ['order' => ['ik', 'ging', 'gisteren', 'zwemmen']], ['order' => ['ik', 'gisteren', 'ging', 'zwemmen']]],
    'mark_error' => ['markError', ['index' => 1], ['index' => 0]],
    'parse_sentence' => ['parseSentence', ['roles' => ['onderwerp', 'persoonsvorm', 'lijdend voorwerp']], ['roles' => ['onderwerp', 'persoonsvorm', 'onderwerp']]],
]);

it('returns the correct answer so the student can learn from a mistake', function (): void {
    student();
    $mc = Question::factory()->create();
    $mark = Question::factory()->markError()->create();
    $fill = Question::factory()->fillBlank()->create();

    $this->postJson("/api/v1/questions/{$mc->id}/answer", ['answer' => ['selected' => [0]]])
        ->assertJsonPath('data.correct_answer.selected', [1]);
    $this->postJson("/api/v1/questions/{$mark->id}/answer", ['answer' => ['index' => 0]])
        ->assertJsonPath('data.correct_answer.index', 1)
        ->assertJsonPath('data.correct_answer.correction', 'fietsen');
    $this->postJson("/api/v1/questions/{$fill->id}/answer", ['answer' => ['blanks' => ['x']]])
        ->assertJsonPath('data.correct_answer.blanks', ['werkt']);
});

it('reports per blank and per token results', function (): void {
    student();
    $parse = Question::factory()->parseSentence()->create();

    $this->postJson("/api/v1/questions/{$parse->id}/answer", ['answer' => ['roles' => ['onderwerp', 'persoonsvorm', 'onderwerp']]])
        ->assertJsonPath('data.items', [true, true, false]);
});

it('rejects malformed answers', function (string $state, array $payload): void {
    student();
    $question = Question::factory()->{$state}()->create();

    $this->postJson("/api/v1/questions/{$question->id}/answer", $payload)->assertUnprocessable();
    expect(Attempt::count())->toBe(0);
})->with([
    'no answer' => ['multipleChoice', []],
    'answer is not an object' => ['multipleChoice', ['answer' => 'b']],
    'option does not exist' => ['multipleChoice', ['answer' => ['selected' => [7]]]],
    'no option chosen' => ['multipleChoice', ['answer' => ['selected' => []]]],
    'wrong number of blanks' => ['fillBlank', ['answer' => ['blanks' => ['a', 'b']]]],
    'wrong number of words' => ['orderWords', ['answer' => ['order' => ['ik']]]],
    'token outside sentence' => ['markError', ['answer' => ['index' => 9]]],
    'unknown role' => ['parseSentence', ['answer' => ['roles' => ['onderwerp', 'persoonsvorm', 'verzonnen']]]],
    'negative time' => ['multipleChoice', ['answer' => ['selected' => [1]], 'time_spent_seconds' => -5]],
]);

it('treats unpublished and unknown questions as not found', function (): void {
    student();
    $hidden = Question::factory()->unpublished()->create();

    $this->postJson("/api/v1/questions/{$hidden->id}/answer", ['answer' => ['selected' => [1]]])->assertNotFound();
    $this->postJson('/api/v1/questions/9999/answer', ['answer' => ['selected' => [1]]])->assertNotFound();
});

it('starts a practice session and records the answer', function (): void {
    $user = student();
    $question = Question::factory()->create();

    $attemptId = $this->postJson("/api/v1/questions/{$question->id}/answer", ['answer' => ['selected' => [1]], 'time_spent_seconds' => 12])
        ->json('data.attempt_id');

    $attempt = Attempt::findOrFail($attemptId);
    expect($attempt->user_id)->toBe($user->id)
        ->and($attempt->mode)->toBe(AttemptMode::Practice)
        ->and($attempt->exam_id)->toBeNull()
        ->and((float) $attempt->score)->toBe(100.0)
        ->and($attempt->isFinished())->toBeTrue()
        ->and($attempt->answers)->toHaveCount(1)
        ->and($attempt->answers->first()->is_correct)->toBeTrue()
        ->and($attempt->answers->first()->time_spent_seconds)->toBe(12)
        ->and($attempt->answers->first()->answer)->toBe(['selected' => [1]]);
});

it('keeps adding to the same session and updates its running score', function (): void {
    student();
    [$a, $b, $c] = Question::factory()->count(3)->create();

    $attemptId = $this->postJson("/api/v1/questions/{$a->id}/answer", ['answer' => ['selected' => [1]]])->json('data.attempt_id');
    $this->postJson("/api/v1/questions/{$b->id}/answer", ['answer' => ['selected' => [0]], 'attempt_id' => $attemptId])
        ->assertJsonPath('data.attempt_id', $attemptId);
    $this->postJson("/api/v1/questions/{$c->id}/answer", ['answer' => ['selected' => [1]], 'attempt_id' => $attemptId]);

    $attempt = Attempt::findOrFail($attemptId);
    expect(Attempt::count())->toBe(1)
        ->and($attempt->answers)->toHaveCount(3)
        ->and((float) $attempt->score)->toBe(66.67);
});

it('does not accept somebody else\'s session or an exam attempt', function (): void {
    student();
    $question = Question::factory()->create();
    $foreign = Attempt::factory()->create();
    $exam = Attempt::factory()->exam()->create(['user_id' => auth('sanctum')->id() ?? User::factory()]);

    $this->postJson("/api/v1/questions/{$question->id}/answer", ['answer' => ['selected' => [1]], 'attempt_id' => $foreign->id])
        ->assertUnprocessable()->assertJsonValidationErrors('attempt_id');
    $this->postJson("/api/v1/questions/{$question->id}/answer", ['answer' => ['selected' => [1]], 'attempt_id' => $exam->id])
        ->assertUnprocessable()->assertJsonValidationErrors('attempt_id');

    expect($foreign->answers()->count())->toBe(0);
});
