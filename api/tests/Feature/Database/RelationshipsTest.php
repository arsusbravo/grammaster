<?php

declare(strict_types=1);

use App\Enums\AttemptMode;
use App\Enums\QuestionType;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Exam;
use App\Models\Level;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\QueryException;

it('links questions to a topic and a level', function (): void {
    $question = Question::factory()->create();

    expect($question->topic)->toBeInstanceOf(Topic::class)
        ->and($question->level)->toBeInstanceOf(Level::class)
        ->and($question->topic->questions)->toHaveCount(1)
        ->and($question->level->questions)->toHaveCount(1);
});

it('casts question type and data', function (): void {
    $question = Question::factory()->fillBlank()->create()->fresh();

    expect($question->type)->toBe(QuestionType::FillBlank)
        ->and($question->data['blanks'][0]['accepted'])->toBe(['werkt'])
        ->and($question->is_published)->toBeTrue();
});

it('only returns published questions from the published scope', function (): void {
    Question::factory()->create();
    Question::factory()->unpublished()->create();

    expect(Question::published()->count())->toBe(1);
});

it('orders exam questions by sort_order and exposes points', function (): void {
    $exam = Exam::factory()->create();
    [$a, $b] = Question::factory()->count(2)->create();

    $exam->questions()->attach([
        $a->id => ['points' => 1, 'sort_order' => 2],
        $b->id => ['points' => 3, 'sort_order' => 1],
    ]);

    expect($exam->questions->pluck('id')->all())->toBe([$b->id, $a->id])
        ->and($exam->questions->first()->pivot->points)->toBe(3)
        ->and($exam->totalPoints())->toBe(4)
        ->and($a->exams)->toHaveCount(1);
});

it('does not allow the same question twice in one exam', function (): void {
    $exam = Exam::factory()->create();
    $question = Question::factory()->create();

    $exam->questions()->attach($question->id);
    $exam->questions()->attach($question->id);
})->throws(QueryException::class);

it('stores attempts and answers for a user', function (): void {
    $user = User::factory()->forLevel()->create();
    $exam = Exam::factory()->create();
    $attempt = Attempt::factory()->exam($exam)->for($user)->create();
    AttemptAnswer::factory()->for($attempt)->create();
    AttemptAnswer::factory()->for($attempt)->incorrect()->create();

    expect($attempt->mode)->toBe(AttemptMode::Exam)
        ->and($attempt->exam->is($exam))->toBeTrue()
        ->and($attempt->answers)->toHaveCount(2)
        ->and($attempt->answers->first()->answer)->toBeArray()
        ->and($attempt->isFinished())->toBeFalse()
        ->and($user->attempts)->toHaveCount(1)
        ->and($user->level)->toBeInstanceOf(Level::class)
        ->and($exam->attempts)->toHaveCount(1);
});

it('keeps attempts of a deleted exam but removes the link', function (): void {
    $attempt = Attempt::factory()->exam()->finished(80)->create();

    $attempt->exam->delete();

    expect($attempt->fresh()->exam_id)->toBeNull()
        ->and((float) $attempt->fresh()->score)->toBe(80.0);
});

it('refuses to delete a question that students have answered', function (): void {
    $answer = AttemptAnswer::factory()->create();

    $answer->question->delete();
})->throws(QueryException::class);

it('removes answers when an attempt is deleted', function (): void {
    $answer = AttemptAnswer::factory()->create();

    $answer->attempt->delete();

    expect(AttemptAnswer::count())->toBe(0);
});

it('clears the chosen level of a user when the level is removed', function (): void {
    $user = User::factory()->forLevel()->create();

    $user->level->delete();

    expect($user->fresh()->level_id)->toBeNull();
});

it('does not allow mass assigning is_admin', function (): void {
    $user = User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'secret123', 'is_admin' => true]);

    expect($user->fresh()->is_admin)->toBeFalse();
});
