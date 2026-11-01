<?php

declare(strict_types=1);

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;

/** Record $correct right and $wrong wrong scored answers on a topic for the user. */
function answerTopic(User $user, Topic $topic, int $correct, int $wrong, ?Attempt $attempt = null): Attempt
{
    $attempt ??= Attempt::factory()->for($user)->finished()->create();

    foreach (range(1, $correct + $wrong) as $i) {
        AttemptAnswer::factory()->for($attempt)->create([
            'question_id' => Question::factory()->create(['topic_id' => $topic->id]),
            'is_correct' => $i <= $correct,
        ]);
    }

    return $attempt;
}

it('requires authentication', function (): void {
    $this->getJson('/api/v1/progress')->assertUnauthorized();
});

it('is empty for a new student but lists every topic', function (): void {
    student();
    Topic::factory()->count(2)->create();

    $this->getJson('/api/v1/progress')
        ->assertOk()
        ->assertJsonPath('data.overall', ['answered' => 0, 'correct' => 0, 'accuracy' => null])
        ->assertJsonCount(2, 'data.topics')
        ->assertJsonPath('data.topics.0.answered', 0)
        ->assertJsonPath('data.topics.0.accuracy', null)
        ->assertJsonPath('data.weak_topics', [])
        ->assertJsonPath('data.history', []);
});

it('computes accuracy per topic and overall', function (): void {
    $user = student();
    $a = Topic::factory()->create(['sort_order' => 1]);
    $b = Topic::factory()->create(['sort_order' => 2]);
    answerTopic($user, $a, correct: 3, wrong: 1);
    answerTopic($user, $b, correct: 1, wrong: 1);

    $this->getJson('/api/v1/progress')
        ->assertJsonPath('data.topics.0.topic.id', $a->id)
        ->assertJsonPath('data.topics.0.answered', 4)
        ->assertJsonPath('data.topics.0.correct', 3)
        ->assertJsonPath('data.topics.0.accuracy', 75)
        ->assertJsonPath('data.topics.1.accuracy', 50)
        ->assertJsonPath('data.overall.answered', 6)
        ->assertJsonPath('data.overall.correct', 4)
        ->assertJsonPath('data.overall.accuracy', 66.7);
});

it('tells clients the rule behind weak topics', function (): void {
    student();

    $this->getJson('/api/v1/progress')->assertJsonPath('data.weak_criteria', ['min_answers' => 3, 'below_percent' => 70]);
});

it('lists weak topics: enough answers, below 70%, weakest first', function (): void {
    $user = student();
    $weakest = Topic::factory()->create();
    $weak = Topic::factory()->create();
    $borderline = Topic::factory()->create();
    $strong = Topic::factory()->create();
    $tooFew = Topic::factory()->create();
    answerTopic($user, $weakest, correct: 1, wrong: 4);   // 20%
    answerTopic($user, $weak, correct: 6, wrong: 4);      // 60%
    answerTopic($user, $borderline, correct: 7, wrong: 3); // 70% is not weak
    answerTopic($user, $strong, correct: 9, wrong: 1);
    answerTopic($user, $tooFew, correct: 0, wrong: 2);    // only 2 answers

    $response = $this->getJson('/api/v1/progress')->assertOk();

    expect($response->json('data.weak_topics.*.topic.id'))->toBe([$weakest->id, $weak->id])
        ->and($response->json('data.weak_topics.0.accuracy'))->toBe(20);
});

it('shows at most three weak topics', function (): void {
    $user = student();
    Topic::factory()->count(5)->create()->each(fn ($t) => answerTopic($user, $t, correct: 0, wrong: 3));

    $this->getJson('/api/v1/progress')->assertJsonCount(3, 'data.weak_topics');
});

it('ignores unscored exam answers and other students', function (): void {
    $user = student();
    $topic = Topic::factory()->create();
    $exam = examWithQuestions(1);
    $running = Attempt::factory()->exam($exam)->for($user)->create();
    AttemptAnswer::factory()->for($running)->create(['question_id' => Question::factory()->create(['topic_id' => $topic->id]), 'is_correct' => null]);
    answerTopic(User::factory()->create(), $topic, correct: 5, wrong: 5);

    $this->getJson('/api/v1/progress')->assertJsonPath('data.overall.answered', 0);
});

it('lists the history newest first with exam results', function (): void {
    $user = student();
    $topic = Topic::factory()->create();
    $practice = answerTopic($user, $topic, correct: 2, wrong: 1);
    $practice->update(['finished_at' => now()->subDays(2), 'score' => 66.67]);

    $exam = examWithQuestions(2, 1, ['pass_mark_percent' => 60, 'title' => 'Oefenexamen X']);
    $examAttempt = Attempt::factory()->exam($exam)->for($user)->create(['finished_at' => now()->subDay(), 'score' => 75]);
    AttemptAnswer::factory()->for($examAttempt)->create(['question_id' => $exam->questions[0]->id, 'is_correct' => true]);
    Attempt::factory()->for($user)->create(); // unfinished: not in history

    $history = $this->getJson('/api/v1/progress')->assertOk()->json('data.history');

    expect($history)->toHaveCount(2)
        ->and($history[0])->toMatchArray([
            'id' => $examAttempt->id, 'mode' => 'exam', 'score' => 75, 'passed' => true,
            'answered' => 1, 'correct' => 1, 'total' => 2, 'exam' => ['id' => $exam->id, 'title' => 'Oefenexamen X'],
        ])
        ->and($history[1])->toMatchArray([
            'id' => $practice->id, 'mode' => 'practice', 'score' => 66.67, 'passed' => null,
            'answered' => 3, 'correct' => 2, 'total' => 3, 'exam' => null,
        ]);
});

it('limits the history to the 20 most recent attempts', function (): void {
    $user = student();
    Attempt::factory()->for($user)->finished()->count(25)->create();

    $this->getJson('/api/v1/progress')->assertJsonCount(20, 'data.history');
});

it('reflects practice answers given through the API', function (): void {
    student();
    $topic = Topic::factory()->create();
    $question = Question::factory()->create(['topic_id' => $topic->id]);
    $this->postJson("/api/v1/questions/{$question->id}/answer", ['answer' => ['selected' => [1]]]);
    $this->postJson("/api/v1/questions/{$question->id}/answer", ['answer' => ['selected' => [0]]]);

    $this->getJson('/api/v1/progress')
        ->assertJsonPath('data.topics.0.answered', 2)
        ->assertJsonPath('data.topics.0.accuracy', 50)
        ->assertJsonCount(2, 'data.history');
});
