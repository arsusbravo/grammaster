<?php

declare(strict_types=1);

use App\Models\Attempt;
use App\Models\Level;
use App\Models\Question;

function sampleLevel(string $slug, int $order): Level
{
    return Level::factory()->create(['slug' => $slug, 'name' => strtoupper($slug), 'sort_order' => $order]);
}

it('lists at most one sample question per simple type for each level, without solutions', function (): void {
    $vmbo = sampleLevel('vmbo', 1);
    $havo = sampleLevel('havo', 2);
    sampleLevel('vwo', 3); // no questions: left out

    foreach ([$vmbo, $havo] as $level) {
        Question::factory()->create(['level_id' => $level->id]);
        Question::factory()->create(['level_id' => $level->id]); // second multiple choice: not a sample
        Question::factory()->fillBlank()->create(['level_id' => $level->id]);
        Question::factory()->markError()->create(['level_id' => $level->id]);
        Question::factory()->orderWords()->create(['level_id' => $level->id]); // not a simple type
        Question::factory()->create(['level_id' => $level->id, 'is_published' => false]);
    }

    $response = $this->getJson('/api/v1/samples')->assertOk();

    expect($response->json('data'))->toHaveCount(2);
    expect(array_column(array_column($response->json('data'), 'level'), 'slug'))->toBe(['vmbo', 'havo']);

    foreach ($response->json('data') as $group) {
        expect(array_column($group['questions'], 'type'))->toBe(['multiple_choice', 'fill_blank', 'mark_error']);
    }

    // Never the solution or the explanation.
    expect($response->getContent())->not->toContain('explanation')
        ->not->toContain('"correct"')
        ->not->toContain('accepted')
        ->not->toContain('error_index')
        ->not->toContain('correction');
});

it('does not need an account to list or answer samples', function (): void {
    $question = Question::factory()->create(['explanation' => 'Dit is de uitleg.']);

    $this->getJson('/api/v1/samples')->assertOk();
    $this->postJson("/api/v1/samples/{$question->id}/answer", ['answer' => ['selected' => [1]]])
        ->assertOk()
        ->assertJsonPath('data.is_correct', true)
        ->assertJsonPath('data.explanation', 'Dit is de uitleg.')
        ->assertJsonPath('data.attempt_id', null);
});

it('checks samples on the server for each simple type and returns the right answer', function (string $state, array $right, array $wrong, array $correctAnswer): void {
    $question = Question::factory()->{$state}()->create();
    $url = "/api/v1/samples/{$question->id}/answer";

    $this->postJson($url, ['answer' => $right])->assertOk()->assertJsonPath('data.is_correct', true);
    $this->postJson($url, ['answer' => $wrong])
        ->assertOk()
        ->assertJsonPath('data.is_correct', false)
        ->assertJsonPath('data.correct_answer.'.array_key_first($correctAnswer), $correctAnswer[array_key_first($correctAnswer)]);
})->with([
    'multiple_choice' => ['multipleChoice', ['selected' => [1]], ['selected' => [0]], ['selected' => [1]]],
    'fill_blank' => ['fillBlank', ['blanks' => ['werkt']], ['blanks' => ['werk']], ['blanks' => ['werkt']]],
    'mark_error' => ['markError', ['index' => 1], ['index' => 0], ['index' => 1]],
]);

it('stores nothing when a sample is answered', function (): void {
    $question = Question::factory()->create();

    $this->postJson("/api/v1/samples/{$question->id}/answer", ['answer' => ['selected' => [1]]])->assertOk();

    expect(Attempt::count())->toBe(0);
});

it('only answers questions that are samples, so the bank cannot be read without an account', function (): void {
    $sample = Question::factory()->create();
    $secondOfSameType = Question::factory()->create(['level_id' => $sample->level_id]);
    $notSimple = Question::factory()->orderWords()->create();
    $unpublished = Question::factory()->unpublished()->create();

    $this->postJson("/api/v1/samples/{$sample->id}/answer", ['answer' => ['selected' => [1]]])->assertOk();

    foreach ([$secondOfSameType, $notSimple, $unpublished] as $question) {
        $this->postJson("/api/v1/samples/{$question->id}/answer", ['answer' => ['selected' => [1]]])->assertNotFound();
    }
});

it('rejects an answer of the wrong shape', function (): void {
    $question = Question::factory()->create();

    $this->postJson("/api/v1/samples/{$question->id}/answer", ['answer' => ['index' => 1]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['answer.selected']);
    $this->postJson("/api/v1/samples/{$question->id}/answer", [])->assertUnprocessable();
});

it('limits how often samples can be answered from one address', function (): void {
    $question = Question::factory()->create();

    foreach (range(1, 30) as $_) {
        $this->postJson("/api/v1/samples/{$question->id}/answer", ['answer' => ['selected' => [1]]])->assertOk();
    }

    $this->postJson("/api/v1/samples/{$question->id}/answer", ['answer' => ['selected' => [1]]])->assertTooManyRequests();
});
