<?php

declare(strict_types=1);

use App\Models\Exam;
use App\Models\Passage;
use App\Models\Question;
use Illuminate\Database\QueryException;

const PASSAGE_BODY = "Sanne zocht overal naar haar sleutel.\n\nUiteindelijk vond ze hem in de koelkast.";

it('sends the text with a practice question and null for a normal question', function (): void {
    student();
    $passage = Passage::factory()->create(['title' => 'De verloren sleutel', 'body' => PASSAGE_BODY]);
    $about = Question::factory()->withPassage($passage)->create();
    $normal = Question::factory()->create();

    $data = collect($this->getJson('/api/v1/questions?limit=50')->assertOk()->json('data'))->keyBy('id');

    expect($data[$about->id]['passage'])->toBe(['id' => $passage->id, 'title' => 'De verloren sleutel', 'body' => PASSAGE_BODY])
        ->and($data[$normal->id])->toHaveKey('passage')
        ->and($data[$normal->id]['passage'])->toBeNull();
});

it('does not give away the answer through the text of a question', function (): void {
    student();
    Question::factory()->withPassage()->create();

    $content = $this->getJson('/api/v1/questions')->assertOk()->getContent();

    expect($content)->not->toContain('"correct"')->not->toContain('explanation');
});

it('shows the text in an exam run, a resumed attempt and the result review', function (): void {
    student();
    $passage = Passage::factory()->create(['title' => 'De verloren sleutel']);
    $exam = Exam::factory()->create();
    $about = Question::factory()->withPassage($passage)->create(['level_id' => $exam->level_id]);
    $normal = Question::factory()->create(['level_id' => $exam->level_id]);
    $exam->questions()->attach($about->id, ['points' => 1, 'sort_order' => 1]);
    $exam->questions()->attach($normal->id, ['points' => 1, 'sort_order' => 2]);

    $start = $this->postJson("/api/v1/exams/{$exam->id}/start")->assertCreated();
    $id = $start->json('data.id');

    $start->assertJsonPath('data.questions.0.passage.title', 'De verloren sleutel')
        ->assertJsonPath('data.questions.1.passage', null);
    $this->getJson("/api/v1/attempts/{$id}")->assertJsonPath('data.questions.0.passage.id', $passage->id);

    $this->postJson("/api/v1/attempts/{$id}/submit")->assertOk();
    $this->getJson("/api/v1/attempts/{$id}/result")
        ->assertJsonPath('data.review.0.question.passage.title', 'De verloren sleutel')
        ->assertJsonPath('data.review.1.question.passage', null);
});

it('scores each question about a text on its own', function (): void {
    student();
    $passage = Passage::factory()->create();
    $exam = Exam::factory()->create();
    $right = Question::factory()->withPassage($passage)->create(['level_id' => $exam->level_id]);
    $wrong = Question::factory()->withPassage($passage)->create(['level_id' => $exam->level_id]);
    $exam->questions()->attach($right->id, ['points' => 1, 'sort_order' => 1]);
    $exam->questions()->attach($wrong->id, ['points' => 1, 'sort_order' => 2]);

    $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
    $this->putJson("/api/v1/attempts/{$id}/answers/{$right->id}", ['answer' => ['selected' => [1]]]);
    $this->putJson("/api/v1/attempts/{$id}/answers/{$wrong->id}", ['answer' => ['selected' => [0]]]);

    $this->postJson("/api/v1/attempts/{$id}/submit")
        ->assertOk()
        ->assertJsonPath('data.points_earned', 1)
        ->assertJsonPath('data.points_total', 2);
});

it('never offers a question about a text as a landing page sample', function (): void {
    $about = Question::factory()->withPassage()->create();
    $plain = Question::factory()->create(['level_id' => $about->level_id]);

    $ids = collect($this->getJson('/api/v1/samples')->assertOk()->json('data'))
        ->flatMap(fn (array $group) => array_column($group['questions'], 'id'))
        ->all();

    expect($ids)->toContain($plain->id)->not->toContain($about->id);

    $this->postJson("/api/v1/samples/{$about->id}/answer", ['answer' => ['selected' => [1]]])->assertNotFound();
});

it('refuses to delete a text that still has questions', function (): void {
    $passage = Passage::factory()->create();
    Question::factory()->withPassage($passage)->create();

    expect(fn () => $passage->delete())->toThrow(QueryException::class);
});
