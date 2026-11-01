<?php

declare(strict_types=1);

use App\Enums\QuestionType;
use App\Models\Level;
use App\Models\Question;
use App\Models\Topic;
use Database\Seeders\DatabaseSeeder;

/** All keys (at any depth) in a decoded JSON structure. */
function allKeys(mixed $value): array
{
    if (! is_array($value)) {
        return [];
    }

    $keys = [];
    foreach ($value as $key => $item) {
        $keys[] = (string) $key;
        $keys = [...$keys, ...allKeys($item)];
    }

    return $keys;
}

it('requires authentication', function (): void {
    $this->getJson('/api/v1/questions')->assertUnauthorized();
});

it('returns only published questions', function (): void {
    student();
    Question::factory()->count(2)->create();
    Question::factory()->unpublished()->create();

    $this->getJson('/api/v1/questions')->assertOk()->assertJsonCount(2, 'data');
});

it('has the documented shape', function (): void {
    student();
    Question::factory()->create(['difficulty' => 2]);

    $this->getJson('/api/v1/questions')->assertJsonStructure(['data' => [[
        'id', 'type', 'prompt', 'difficulty', 'topic' => ['id', 'slug', 'name'], 'level' => ['id', 'slug', 'name'], 'data',
    ]]]);
});

it('filters by topic, level and difficulty', function (): void {
    student();
    $topic = Topic::factory()->create(['slug' => 'lidwoorden']);
    $level = Level::factory()->create(['slug' => 'havo']);
    $match = Question::factory()->create(['topic_id' => $topic->id, 'level_id' => $level->id, 'difficulty' => 3]);
    Question::factory()->create(['topic_id' => $topic->id, 'level_id' => $level->id, 'difficulty' => 1]);
    Question::factory()->create(['topic_id' => $topic->id, 'difficulty' => 3]);
    Question::factory()->create(['level_id' => $level->id, 'difficulty' => 3]);

    $this->getJson('/api/v1/questions?topic=lidwoorden&level=havo&difficulty=3')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);

    $this->getJson('/api/v1/questions?topic=lidwoorden')->assertJsonCount(3, 'data');
    $this->getJson('/api/v1/questions?level=havo')->assertJsonCount(3, 'data');
    $this->getJson('/api/v1/questions?difficulty=3')->assertJsonCount(3, 'data');
});

it('limits and excludes', function (): void {
    student();
    $questions = Question::factory()->count(5)->create();

    $this->getJson('/api/v1/questions?limit=2')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/questions')->assertJsonCount(5, 'data');

    $ids = collect($this->getJson('/api/v1/questions?exclude[]='.$questions[0]->id.'&exclude[]='.$questions[1]->id)->json('data'))->pluck('id');
    expect($ids)->toHaveCount(3)->and($ids)->not->toContain($questions[0]->id, $questions[1]->id);
});

it('returns the questions in random order', function (): void {
    student();
    Question::factory()->count(12)->create();

    $orders = collect(range(1, 6))->map(fn () => implode(',', collect($this->getJson('/api/v1/questions?limit=12')->json('data'))->pluck('id')->all()));

    expect($orders->unique()->count())->toBeGreaterThan(1);
});

it('validates the filters', function (string $query, string $field): void {
    student();

    $this->getJson("/api/v1/questions?$query")->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    ['topic=bestaat-niet', 'topic'],
    ['level=bestaat-niet', 'level'],
    ['difficulty=9', 'difficulty'],
    ['difficulty=abc', 'difficulty'],
    ['limit=0', 'limit'],
    ['limit=500', 'limit'],
]);

it('never exposes answers or explanations, for every seeded question', function (): void {
    $this->seed(DatabaseSeeder::class);
    student();

    $data = $this->getJson('/api/v1/questions?limit=50')->assertOk()->json('data');
    $forbidden = ['correct', 'accepted', 'error_index', 'correction', 'alternative_orders', 'explanation', 'role', 'case_sensitive'];

    expect($data)->toHaveCount(35);
    foreach ($data as $question) {
        expect(array_intersect(allKeys($question), $forbidden))->toBe([], "question {$question['id']} leaks");
    }
});

it('sends order_words in a shuffled order', function (): void {
    student();
    $question = Question::factory()->orderWords()->create();

    foreach (range(1, 10) as $_) {
        $words = $this->getJson('/api/v1/questions')->json('data.0.data.words');
        expect($words)->not->toBe($question->data['words']);
    }
});

it('covers all five question types in the public shape', function (): void {
    student();
    foreach (QuestionType::cases() as $type) {
        Question::factory()->{str($type->value)->camel()->toString()}()->create();
    }

    $byType = collect($this->getJson('/api/v1/questions?limit=50')->json('data'))->keyBy('type');

    expect($byType['multiple_choice']['data'])->toHaveKeys(['options', 'multiple'])
        ->and($byType['fill_blank']['data'])->toHaveKeys(['text', 'blanks'])
        ->and($byType['order_words']['data'])->toHaveKeys(['words'])
        ->and($byType['mark_error']['data'])->toHaveKeys(['tokens'])
        ->and($byType['parse_sentence']['data'])->toHaveKeys(['tokens', 'roles'])
        ->and($byType['parse_sentence']['data']['tokens'])->toBe(['De kat', 'eet', 'de vis']);
});
