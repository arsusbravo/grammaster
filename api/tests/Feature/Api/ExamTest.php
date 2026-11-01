<?php

declare(strict_types=1);

use App\Enums\AttemptMode;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Level;
use App\Models\Question;

describe('GET /exams', function (): void {
    it('requires authentication', function (): void {
        $this->getJson('/api/v1/exams')->assertUnauthorized();
    });

    it('lists published exams with counts and points', function (): void {
        student();
        $exam = examWithQuestions(3, [1, 2, 3], ['title' => 'Oefenexamen A', 'time_limit_minutes' => 20, 'pass_mark_percent' => 60]);
        examWithQuestions(1, 1, ['is_published' => false]);

        $this->getJson('/api/v1/exams')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $exam->id)
            ->assertJsonPath('data.0.title', 'Oefenexamen A')
            ->assertJsonPath('data.0.question_count', 3)
            ->assertJsonPath('data.0.total_points', 6)
            ->assertJsonPath('data.0.time_limit_minutes', 20)
            ->assertJsonPath('data.0.pass_mark_percent', 60)
            ->assertJsonPath('data.0.level.id', $exam->level_id)
            ->assertJsonPath('data.0.in_progress_attempt_id', null)
            ->assertJsonPath('data.0.best_score', null)
            ->assertJsonPath('data.0.attempts_count', 0);
    });

    it('filters by level', function (): void {
        student();
        $vmbo = Level::factory()->create(['slug' => 'vmbo']);
        examWithQuestions(1, 1, ['level_id' => $vmbo->id]);
        examWithQuestions(1);

        $this->getJson('/api/v1/exams?level=vmbo')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/exams')->assertJsonCount(2, 'data');
    });

    it('shows the student\'s own progress only', function (): void {
        $user = student();
        $exam = examWithQuestions();
        Attempt::factory()->exam($exam)->for($user)->finished(40)->create();
        Attempt::factory()->exam($exam)->for($user)->finished(75.5)->create();
        $running = Attempt::factory()->exam($exam)->for($user)->create();
        Attempt::factory()->exam($exam)->finished(99)->create();

        $this->getJson('/api/v1/exams')
            ->assertJsonPath('data.0.in_progress_attempt_id', $running->id)
            ->assertJsonPath('data.0.best_score', '75.50')
            ->assertJsonPath('data.0.attempts_count', 2);
    });
});

describe('POST /exams/{exam}/start', function (): void {
    it('requires authentication', function (): void {
        $this->postJson('/api/v1/exams/'.examWithQuestions()->id.'/start')->assertUnauthorized();
    });

    it('starts an attempt with the questions in order and no answers', function (): void {
        $user = student();
        $exam = examWithQuestions(3, [1, 2, 3], ['time_limit_minutes' => 30]);

        $response = $this->postJson("/api/v1/exams/{$exam->id}/start")
            ->assertCreated()
            ->assertJsonPath('data.exam.id', $exam->id)
            ->assertJsonPath('data.exam.time_limit_minutes', 30)
            ->assertJsonCount(3, 'data.questions')
            ->assertJsonPath('data.questions.0.points', 1)
            ->assertJsonPath('data.questions.2.points', 3)
            ->assertJsonPath('data.remaining_seconds', 1800)
            ->assertJsonPath('data.finished_at', null);

        expect($response->json('data.questions.*.id'))->toBe($exam->questions->pluck('id')->all())
            ->and($response->json('data.answers'))->toBe([]);

        $attempt = Attempt::findOrFail($response->json('data.id'));
        expect($attempt->user_id)->toBe($user->id)
            ->and($attempt->mode)->toBe(AttemptMode::Exam)
            ->and($attempt->exam_id)->toBe($exam->id)
            ->and($attempt->isFinished())->toBeFalse();
    });

    it('does not leak answers or explanations while the exam runs', function (): void {
        student();
        $exam = examWithQuestions(2);

        $json = $this->postJson("/api/v1/exams/{$exam->id}/start")->getContent();

        expect($json)->not->toContain('explanation')->not->toContain('"correct"')->not->toContain('correct_answer');
    });

    it('resumes the running attempt with its saved answers', function (): void {
        student();
        $exam = examWithQuestions(2);
        $attemptId = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        $question = $exam->questions->first();
        $this->putJson("/api/v1/attempts/{$attemptId}/answers/{$question->id}", ['answer' => ['selected' => [2]]])->assertNoContent();

        $this->travel(5)->minutes();

        $this->postJson("/api/v1/exams/{$exam->id}/start")
            ->assertOk()
            ->assertJsonPath('data.id', $attemptId)
            ->assertJsonPath('data.answers.'.$question->id.'.selected', [2])
            ->assertJsonPath('data.remaining_seconds', 25 * 60);

        expect(Attempt::count())->toBe(1);
    });

    it('scores an attempt whose time ran out and starts a fresh one', function (): void {
        student();
        $exam = examWithQuestions(2, 1, ['time_limit_minutes' => 10]);
        $oldId = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        $this->putJson("/api/v1/attempts/{$oldId}/answers/{$exam->questions[0]->id}", ['answer' => ['selected' => [1]]]);

        $this->travel(11)->minutes();

        $newId = $this->postJson("/api/v1/exams/{$exam->id}/start")->assertCreated()->json('data.id');

        $old = Attempt::findOrFail($oldId);
        expect($newId)->not->toBe($oldId)
            ->and($old->isFinished())->toBeTrue()
            ->and((float) $old->score)->toBe(50.0);
    });

    it('lets different students run the same exam independently', function (): void {
        $exam = examWithQuestions(1);
        student();
        $first = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        student();
        $second = $this->postJson("/api/v1/exams/{$exam->id}/start")->assertCreated()->json('data.id');

        expect($second)->not->toBe($first);
    });

    it('treats unpublished exams as not found', function (): void {
        student();
        $exam = examWithQuestions(1, 1, ['is_published' => false]);

        $this->postJson("/api/v1/exams/{$exam->id}/start")->assertNotFound();
    });
});

it('requires authentication to save an exam answer', function (): void {
    $this->putJson('/api/v1/attempts/1/answers/1', ['answer' => ['selected' => [1]]])->assertUnauthorized();
});

describe('PUT /attempts/{attempt}/answers/{question}', function (): void {
    beforeEach(function (): void {
        $this->user = student();
        $this->exam = examWithQuestions(2, 1, ['time_limit_minutes' => 10]);
        $this->attemptId = $this->postJson("/api/v1/exams/{$this->exam->id}/start")->json('data.id');
        $this->question = $this->exam->questions->first();
        $this->url = "/api/v1/attempts/{$this->attemptId}/answers/{$this->question->id}";
    });

    it('saves without scoring or giving feedback and can be overwritten', function (): void {
        $this->putJson($this->url, ['answer' => ['selected' => [1]], 'time_spent_seconds' => 7])
            ->assertNoContent();
        $this->putJson($this->url, ['answer' => ['selected' => [0]], 'time_spent_seconds' => 9])
            ->assertNoContent();

        $rows = Attempt::findOrFail($this->attemptId)->answers;
        expect($rows)->toHaveCount(1)
            ->and($rows->first()->answer)->toBe(['selected' => [0]])
            ->and($rows->first()->is_correct)->toBeNull()
            ->and($rows->first()->time_spent_seconds)->toBe(9);
    });

    it('validates the answer for the question type', function (): void {
        $this->putJson($this->url, ['answer' => ['selected' => [9]]])->assertUnprocessable();
        $this->putJson($this->url, [])->assertUnprocessable();
    });

    it('rejects a question that is not part of the exam', function (): void {
        $other = Question::factory()->create();

        $this->putJson("/api/v1/attempts/{$this->attemptId}/answers/{$other->id}", ['answer' => ['selected' => [1]]])
            ->assertUnprocessable();
    });

    it('is not available for other students', function (): void {
        student();

        $this->putJson($this->url, ['answer' => ['selected' => [1]]])->assertForbidden();
    });

    it('is not available for practice sessions', function (): void {
        $practice = Attempt::factory()->for($this->user)->create();

        $this->putJson("/api/v1/attempts/{$practice->id}/answers/{$this->question->id}", ['answer' => ['selected' => [1]]])
            ->assertNotFound();
    });

    it('accepts answers shortly after the deadline but not later', function (): void {
        $this->travel(10)->minutes();
        $this->travel(10)->seconds();
        $this->putJson($this->url, ['answer' => ['selected' => [1]]])->assertNoContent();

        $this->travel(20)->seconds();
        $this->putJson($this->url, ['answer' => ['selected' => [1]]])->assertConflict();
    });

    it('is closed after submitting', function (): void {
        $this->postJson("/api/v1/attempts/{$this->attemptId}/submit")->assertOk();

        $this->putJson($this->url, ['answer' => ['selected' => [1]]])->assertConflict();
    });
});

describe('POST /attempts/{attempt}/submit', function (): void {
    it('requires authentication', function (): void {
        $this->postJson('/api/v1/attempts/1/submit')->assertUnauthorized();
    });

    it('scores by points and counts unanswered questions as wrong', function (): void {
        student();
        $exam = examWithQuestions(3, [1, 2, 3], ['pass_mark_percent' => 55]);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        [$q1, $q2] = [$exam->questions[0], $exam->questions[1]];
        $this->putJson("/api/v1/attempts/{$id}/answers/{$q1->id}", ['answer' => ['selected' => [1]]]);
        $this->putJson("/api/v1/attempts/{$id}/answers/{$q2->id}", ['answer' => ['selected' => [0]]]);

        $this->postJson("/api/v1/attempts/{$id}/submit")
            ->assertOk()
            ->assertJsonPath('data.score', 16.67)
            ->assertJsonPath('data.points_earned', 1)
            ->assertJsonPath('data.points_total', 6)
            ->assertJsonPath('data.passed', false);

        $attempt = Attempt::findOrFail($id);
        expect($attempt->isFinished())->toBeTrue()
            ->and($attempt->answers->pluck('is_correct', 'question_id')->all())->toBe([$q1->id => true, $q2->id => false]);
    });

    it('passes at or above the pass mark', function (): void {
        student();
        $exam = examWithQuestions(2, 1, ['pass_mark_percent' => 50]);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        $this->putJson("/api/v1/attempts/{$id}/answers/{$exam->questions[0]->id}", ['answer' => ['selected' => [1]]]);

        $this->postJson("/api/v1/attempts/{$id}/submit")
            ->assertJsonPath('data.score', 50)
            ->assertJsonPath('data.passed', true);
    });

    it('scores an empty exam as zero', function (): void {
        student();
        $exam = examWithQuestions(2);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');

        $this->postJson("/api/v1/attempts/{$id}/submit")->assertJsonPath('data.score', 0)->assertJsonPath('data.passed', false);
    });

    it('is idempotent', function (): void {
        student();
        $exam = examWithQuestions(2);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        $this->putJson("/api/v1/attempts/{$id}/answers/{$exam->questions[0]->id}", ['answer' => ['selected' => [1]]]);

        $first = $this->postJson("/api/v1/attempts/{$id}/submit")->json('data');
        $this->travel(1)->hours();
        $second = $this->postJson("/api/v1/attempts/{$id}/submit")->assertOk()->json('data');

        expect($second['score'])->toBe($first['score'])->and($second['finished_at'])->toBe($first['finished_at']);
    });

    it('can be submitted after the deadline and scores what was saved', function (): void {
        student();
        $exam = examWithQuestions(2, 1, ['time_limit_minutes' => 5]);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        $this->putJson("/api/v1/attempts/{$id}/answers/{$exam->questions[0]->id}", ['answer' => ['selected' => [1]]]);

        $this->travel(30)->minutes();

        $this->postJson("/api/v1/attempts/{$id}/submit")->assertOk()->assertJsonPath('data.score', 50);
    });

    it('belongs to the student who started it', function (): void {
        student();
        $exam = examWithQuestions(1);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        student();

        $this->postJson("/api/v1/attempts/{$id}/submit")->assertForbidden();
        expect(Attempt::findOrFail($id)->isFinished())->toBeFalse();
    });

    it('is not available for practice sessions', function (): void {
        $user = student();
        $practice = Attempt::factory()->for($user)->create();

        $this->postJson("/api/v1/attempts/{$practice->id}/submit")->assertNotFound();
    });
});

describe('GET /attempts/{attempt}/result', function (): void {
    it('requires authentication', function (): void {
        $this->getJson('/api/v1/attempts/1/result')->assertUnauthorized();
    });

    it('returns a full review with answers, correct answers and explanations', function (): void {
        student();
        $exam = examWithQuestions(2, [1, 2]);
        $exam->questions[0]->update(['explanation' => 'Uitleg een.']);
        $exam->questions[1]->update(['explanation' => 'Uitleg twee.']);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        $this->putJson("/api/v1/attempts/{$id}/answers/{$exam->questions[0]->id}", ['answer' => ['selected' => [1]]]);
        $this->putJson("/api/v1/attempts/{$id}/answers/{$exam->questions[1]->id}", ['answer' => ['selected' => [2]]]);
        $this->postJson("/api/v1/attempts/{$id}/submit");

        $this->getJson("/api/v1/attempts/{$id}/result")
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.exam.id', $exam->id)
            ->assertJsonPath('data.score', 33.33)
            ->assertJsonPath('data.points_earned', 1)
            ->assertJsonPath('data.points_total', 3)
            ->assertJsonCount(2, 'data.review')
            ->assertJsonPath('data.review.0.question.id', $exam->questions[0]->id)
            ->assertJsonPath('data.review.0.answer.selected', [1])
            ->assertJsonPath('data.review.0.is_correct', true)
            ->assertJsonPath('data.review.0.points_earned', 1)
            ->assertJsonPath('data.review.0.correct_answer.selected', [1])
            ->assertJsonPath('data.review.0.explanation', 'Uitleg een.')
            ->assertJsonPath('data.review.1.answer.selected', [2])
            ->assertJsonPath('data.review.1.is_correct', false)
            ->assertJsonPath('data.review.1.points_earned', 0)
            ->assertJsonPath('data.review.1.points', 2)
            ->assertJsonPath('data.review.1.explanation', 'Uitleg twee.')
            ->assertJsonStructure(['data' => ['started_at', 'finished_at', 'duration_seconds', 'passed']]);
    });

    it('shows unanswered questions as such in the review', function (): void {
        student();
        $exam = examWithQuestions(2);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        $this->postJson("/api/v1/attempts/{$id}/submit");

        $this->getJson("/api/v1/attempts/{$id}/result")
            ->assertJsonCount(2, 'data.review')
            ->assertJsonPath('data.review.0.answer', null)
            ->assertJsonPath('data.review.0.is_correct', false)
            ->assertJsonPath('data.review.0.correct_answer.selected', [1]);
    });

    it('is not available before the exam is finished', function (): void {
        student();
        $exam = examWithQuestions(1);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');

        $this->getJson("/api/v1/attempts/{$id}/result")->assertConflict();
    });

    it('only shows a student their own result', function (): void {
        student();
        $exam = examWithQuestions(1);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        $this->postJson("/api/v1/attempts/{$id}/submit");
        student();

        $this->getJson("/api/v1/attempts/{$id}/result")->assertForbidden();
    });

    it('is not available for practice sessions or unknown attempts', function (): void {
        $user = student();
        $practice = Attempt::factory()->for($user)->finished()->create();

        $this->getJson("/api/v1/attempts/{$practice->id}/result")->assertNotFound();
        $this->getJson('/api/v1/attempts/9999/result')->assertNotFound();
    });

    it('keeps working when the exam gets unpublished afterwards', function (): void {
        student();
        $exam = examWithQuestions(1);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        $this->postJson("/api/v1/attempts/{$id}/submit");
        Exam::whereKey($exam->id)->update(['is_published' => false]);

        $this->getJson("/api/v1/attempts/{$id}/result")->assertOk();
    });
});

describe('GET /attempts/{attempt}', function (): void {
    it('requires authentication', function (): void {
        $this->getJson('/api/v1/attempts/1')->assertUnauthorized();
    });

    it('returns the running attempt with saved answers and the time left', function (): void {
        student();
        $exam = examWithQuestions(2, [1, 2], ['time_limit_minutes' => 20]);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        $question = $exam->questions->first();
        $this->putJson("/api/v1/attempts/{$id}/answers/{$question->id}", ['answer' => ['selected' => [2]]]);
        $this->travel(5)->minutes();

        $this->getJson("/api/v1/attempts/{$id}")
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.finished_at', null)
            ->assertJsonPath('data.remaining_seconds', 15 * 60)
            ->assertJsonPath('data.answers.'.$question->id.'.selected', [2])
            ->assertJsonCount(2, 'data.questions')
            ->assertJsonPath('data.questions.1.points', 2);
    });

    it('does not leak answers or explanations', function (): void {
        student();
        $exam = examWithQuestions(2);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');

        expect($this->getJson("/api/v1/attempts/{$id}")->getContent())
            ->not->toContain('explanation')->not->toContain('correct_answer')->not->toContain('"correct"');
    });

    it('scores an attempt whose time ran out and reports it as finished', function (): void {
        student();
        $exam = examWithQuestions(2, 1, ['time_limit_minutes' => 10]);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        $this->putJson("/api/v1/attempts/{$id}/answers/{$exam->questions[0]->id}", ['answer' => ['selected' => [1]]]);

        $this->travel(11)->minutes();

        $this->getJson("/api/v1/attempts/{$id}")
            ->assertOk()
            ->assertJsonPath('data.remaining_seconds', 0)
            ->assertJsonMissingPath('data.score');
        $attempt = Attempt::findOrFail($id);
        expect($attempt->isFinished())->toBeTrue()->and((float) $attempt->score)->toBe(50.0);
        expect($this->getJson("/api/v1/attempts/{$id}")->json('data.finished_at'))->not->toBeNull();
    });

    it('belongs to the student, and is only for exam attempts', function (): void {
        $user = student();
        $exam = examWithQuestions(1);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        $practice = Attempt::factory()->for($user)->create();

        $this->getJson("/api/v1/attempts/{$practice->id}")->assertNotFound();
        $this->getJson('/api/v1/attempts/9999')->assertNotFound();

        student();
        $this->getJson("/api/v1/attempts/{$id}")->assertForbidden();
    });
});

describe('DELETE /attempts/{attempt}/answers/{question}', function (): void {
    beforeEach(function (): void {
        $this->user = student();
        $this->exam = examWithQuestions(2, 1, ['time_limit_minutes' => 10]);
        $this->attemptId = $this->postJson("/api/v1/exams/{$this->exam->id}/start")->json('data.id');
        $this->question = $this->exam->questions->first();
        $this->url = "/api/v1/attempts/{$this->attemptId}/answers/{$this->question->id}";
    });

    it('requires authentication', function (): void {
        $this->app['auth']->forgetGuards();
        $this->refreshApplication();

        $this->deleteJson('/api/v1/attempts/1/answers/1')->assertUnauthorized();
    });

    it('removes a saved answer so it no longer counts', function (): void {
        $this->putJson($this->url, ['answer' => ['selected' => [1]]])->assertNoContent();

        $this->deleteJson($this->url)->assertNoContent();

        expect(Attempt::findOrFail($this->attemptId)->answers)->toHaveCount(0);
        $this->postJson("/api/v1/attempts/{$this->attemptId}/submit")->assertJsonPath('data.score', 0);
    });

    it('is fine when nothing was saved', function (): void {
        $this->deleteJson($this->url)->assertNoContent();
    });

    it('only touches the given question', function (): void {
        $other = $this->exam->questions[1];
        $this->putJson($this->url, ['answer' => ['selected' => [1]]]);
        $this->putJson("/api/v1/attempts/{$this->attemptId}/answers/{$other->id}", ['answer' => ['selected' => [1]]]);

        $this->deleteJson($this->url)->assertNoContent();

        expect(Attempt::findOrFail($this->attemptId)->answers->pluck('question_id')->all())->toBe([$other->id]);
    });

    it('belongs to the student and is closed after submitting or when time is up', function (): void {
        $this->postJson("/api/v1/attempts/{$this->attemptId}/submit");
        $this->deleteJson($this->url)->assertConflict();

        $exam = examWithQuestions(1, 1, ['time_limit_minutes' => 5]);
        $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
        $this->travel(6)->minutes();
        $this->deleteJson("/api/v1/attempts/{$id}/answers/{$exam->questions[0]->id}")->assertConflict();

        $this->travelBack();
        student();
        $this->deleteJson($this->url)->assertForbidden();
    });
});

it('adds per blank and per token verdicts to the review, also for unanswered questions', function (): void {
    student();
    $exam = Exam::factory()->create();
    $fill = Question::factory()->fillBlank()->create(['level_id' => $exam->level_id]);
    $parse = Question::factory()->parseSentence()->create(['level_id' => $exam->level_id]);
    $parseUnanswered = Question::factory()->parseSentence()->create(['level_id' => $exam->level_id]);
    $mc = Question::factory()->create(['level_id' => $exam->level_id]);
    foreach ([$fill, $parse, $parseUnanswered, $mc] as $i => $q) {
        $exam->questions()->attach($q->id, ['points' => 1, 'sort_order' => $i + 1]);
    }
    $id = $this->postJson("/api/v1/exams/{$exam->id}/start")->json('data.id');
    $this->putJson("/api/v1/attempts/{$id}/answers/{$fill->id}", ['answer' => ['blanks' => ['fout']]]);
    $this->putJson("/api/v1/attempts/{$id}/answers/{$parse->id}", ['answer' => ['roles' => ['onderwerp', 'persoonsvorm', 'onderwerp']]]);
    $this->putJson("/api/v1/attempts/{$id}/answers/{$mc->id}", ['answer' => ['selected' => [1]]]);
    $this->postJson("/api/v1/attempts/{$id}/submit");

    $review = $this->getJson("/api/v1/attempts/{$id}/result")->json('data.review');

    expect($review[0]['items'])->toBe([false])
        ->and($review[1]['items'])->toBe([true, true, false])
        ->and($review[2]['items'])->toBe([false, false, false])
        ->and($review[2]['answer'])->toBeNull()
        ->and($review[3]['items'])->toBeNull();
});

it('links the exam list to the last finished attempt', function (): void {
    $user = student();
    $exam = examWithQuestions(1);
    $first = Attempt::factory()->exam($exam)->for($user)->finished(40)->create();
    $last = Attempt::factory()->exam($exam)->for($user)->finished(80)->create();
    Attempt::factory()->exam($exam)->for($user)->create(); // running: not a result
    Attempt::factory()->exam($exam)->finished(99)->create(); // someone else's

    $this->getJson('/api/v1/exams')
        ->assertJsonPath('data.0.last_attempt_id', $last->id)
        ->assertJsonPath('data.0.attempts_count', 2);
    expect($first->id)->toBeLessThan($last->id);
});
