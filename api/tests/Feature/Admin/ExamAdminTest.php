<?php

declare(strict_types=1);

use App\Filament\Resources\Exams\Pages\CreateExam;
use App\Filament\Resources\Exams\Pages\EditExam;
use App\Filament\Resources\Exams\Pages\ListExams;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Level;
use App\Models\Question;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
    Filament\Facades\Filament::setCurrentPanel('admin');
    $this->level = Level::factory()->create();
});

function examData(array $overrides = []): array
{
    return [
        'title' => 'Oefenexamen test',
        'level_id' => test()->level->id,
        'time_limit_minutes' => 25,
        'pass_mark_percent' => 60,
        'is_published' => true,
        ...$overrides,
    ];
}

describe('create', function (): void {
    it('creates an exam with ordered questions and points', function (): void {
        [$a, $b, $c] = Question::factory()->count(3)->create(['level_id' => $this->level->id]);

        Livewire::test(CreateExam::class)
            ->fillForm(examData(['examQuestions' => [
                ['question_id' => $c->id, 'points' => 3],
                ['question_id' => $a->id, 'points' => 1],
                ['question_id' => $b->id, 'points' => 2],
            ]]))
            ->call('create')
            ->assertHasNoFormErrors();

        $exam = Exam::firstWhere('title', 'Oefenexamen test');
        expect($exam->time_limit_minutes)->toBe(25)
            ->and($exam->pass_mark_percent)->toBe(60)
            ->and($exam->is_published)->toBeTrue()
            ->and($exam->questions->pluck('id')->all())->toBe([$c->id, $a->id, $b->id])
            ->and($exam->questions->pluck('pivot.points')->all())->toBe([3, 1, 2])
            ->and($exam->questions->pluck('pivot.sort_order')->all())->toBe([1, 2, 3])
            ->and($exam->totalPoints())->toBe(6);
    });

    it('can be saved as a draft without questions', function (): void {
        Livewire::test(CreateExam::class)
            ->fillForm(examData(['is_published' => false]))
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Exam::count())->toBe(1);
    });

    it('needs at least one question to be published', function (): void {
        Livewire::test(CreateExam::class)
            ->fillForm(examData(['examQuestions' => []]))
            ->call('create')
            ->assertHasFormErrors(['examQuestions']);

        expect(Exam::count())->toBe(0);
    });

    it('validates the basic fields', function (array $overrides, string $field): void {
        Livewire::test(CreateExam::class)
            ->fillForm(examData(['is_published' => false, ...$overrides]))
            ->call('create')
            ->assertHasFormErrors([$field]);
    })->with([
        'no title' => [['title' => ''], 'title'],
        'no level' => [['level_id' => null], 'level_id'],
        'zero minutes' => [['time_limit_minutes' => 0], 'time_limit_minutes'],
        'pass mark above 100' => [['pass_mark_percent' => 101], 'pass_mark_percent'],
        'pass mark empty' => [['pass_mark_percent' => null], 'pass_mark_percent'],
    ]);

    it('does not allow the same question twice', function (): void {
        $question = Question::factory()->create(['level_id' => $this->level->id]);

        Livewire::test(CreateExam::class)
            ->fillForm(examData(['examQuestions' => [
                ['question_id' => $question->id, 'points' => 1],
                ['question_id' => $question->id, 'points' => 1],
            ]]))
            ->call('create')
            ->assertHasFormErrors();

        expect(Exam::count())->toBe(0);
    });

    it('requires points per question', function (): void {
        $question = Question::factory()->create(['level_id' => $this->level->id]);

        Livewire::test(CreateExam::class)
            ->fillForm(examData(['examQuestions' => [['question_id' => $question->id, 'points' => 0]]]))
            ->call('create')
            ->assertHasFormErrors();
    });

    it('offers only published questions of the exam level', function (): void {
        $ok = Question::factory()->create(['level_id' => $this->level->id]);
        $draft = Question::factory()->unpublished()->create(['level_id' => $this->level->id]);
        $other = Question::factory()->create();

        Livewire::test(CreateExam::class)
            ->fillForm(examData(['is_published' => false, 'examQuestions' => [['question_id' => $draft->id, 'points' => 1]]]))
            ->call('create')
            ->assertHasFormErrors();

        Livewire::test(CreateExam::class)
            ->fillForm(examData(['is_published' => false, 'examQuestions' => [['question_id' => $other->id, 'points' => 1]]]))
            ->call('create')
            ->assertHasFormErrors();

        Livewire::test(CreateExam::class)
            ->fillForm(examData(['is_published' => false, 'examQuestions' => [['question_id' => $ok->id, 'points' => 1]]]))
            ->call('create')
            ->assertHasNoFormErrors();
    });
});

describe('edit', function (): void {
    it('loads the exam with its questions', function (): void {
        $exam = examWithQuestions(2, [1, 2], ['title' => 'Bestaand', 'level_id' => $this->level->id]);

        Livewire::test(EditExam::class, ['record' => $exam->id])
            ->assertFormSet(['title' => 'Bestaand', 'time_limit_minutes' => $exam->time_limit_minutes])
            ->assertSee($exam->questions[0]->summary());
    });

    it('changes the questions, their order and points', function (): void {
        $exam = examWithQuestions(2, 1, ['level_id' => $this->level->id]);
        [$first, $second] = $exam->questions;
        $new = Question::factory()->create(['level_id' => $this->level->id]);

        Livewire::test(EditExam::class, ['record' => $exam->id])
            ->fillForm(['title' => 'Aangepast', 'examQuestions' => [
                ['question_id' => $new->id, 'points' => 5],
                ['question_id' => $second->id, 'points' => 2],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $exam = $exam->fresh();
        expect($exam->title)->toBe('Aangepast')
            ->and($exam->questions->pluck('id')->all())->toBe([$new->id, $second->id])
            ->and($exam->questions->pluck('pivot.points')->all())->toBe([5, 2])
            ->and(DB::table('exam_questions')->where('question_id', $first->id)->exists())->toBeFalse();
    });

    it('refuses to publish an exam that has no questions left', function (): void {
        $exam = examWithQuestions(1, 1, ['level_id' => $this->level->id]);

        Livewire::test(EditExam::class, ['record' => $exam->id])
            ->fillForm(['examQuestions' => []])
            ->call('save')
            ->assertHasFormErrors(['examQuestions']);

        expect($exam->fresh()->questions)->toHaveCount(1);
    });

    it('keeps students\' attempts when the exam is deleted', function (): void {
        $exam = examWithQuestions(1);
        $attempt = Attempt::factory()->exam($exam)->finished(80)->create();

        Livewire::test(EditExam::class, ['record' => $exam->id])->callAction(DeleteAction::class);

        expect(Exam::count())->toBe(0)
            ->and($attempt->fresh()->exam_id)->toBeNull()
            ->and((float) $attempt->fresh()->score)->toBe(80.0)
            ->and(Question::count())->toBe(1);
    });
});

describe('list', function (): void {
    it('shows level, question count, points and time', function (): void {
        $exam = examWithQuestions(3, [1, 2, 3], ['title' => 'Examen A', 'time_limit_minutes' => 45, 'level_id' => $this->level->id]);

        Livewire::test(ListExams::class)
            ->assertCanSeeTableRecords([$exam])
            ->assertTableColumnStateSet('questions_count', 3, $exam)
            ->assertTableColumnStateSet('questions_sum_exam_questionspoints', 6, $exam)
            ->assertSee('Examen A')
            ->assertSee($this->level->name);
    });

    it('filters by level and publication', function (): void {
        $mine = examWithQuestions(1, 1, ['level_id' => $this->level->id]);
        $other = examWithQuestions(1, 1, ['is_published' => false]);

        Livewire::test(ListExams::class)
            ->filterTable('level_id', $this->level->id)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other])
            ->removeTableFilters()
            ->filterTable('is_published', false)
            ->assertCanSeeTableRecords([$other])
            ->assertCanNotSeeTableRecords([$mine]);
    });

    it('publishes and unpublishes an exam, but not an empty one', function (): void {
        $exam = examWithQuestions(1, 1, ['is_published' => false]);
        $empty = Exam::factory()->unpublished()->create();

        Livewire::test(ListExams::class)->callAction(TestAction::make('togglePublished')->table($exam));
        expect($exam->fresh()->is_published)->toBeTrue();

        Livewire::test(ListExams::class)->callAction(TestAction::make('togglePublished')->table($exam));
        expect($exam->fresh()->is_published)->toBeFalse();

        Livewire::test(ListExams::class)
            ->callAction(TestAction::make('togglePublished')->table($empty))
            ->assertNotified('Voeg eerst vragen toe');
        expect($empty->fresh()->is_published)->toBeFalse();
    });

    it('deletes an exam from the list', function (): void {
        $exam = examWithQuestions(1);

        Livewire::test(ListExams::class)->callAction(TestAction::make(DeleteAction::class)->table($exam));

        expect(Exam::count())->toBe(0);
    });
});

it('makes an exam built in the admin available to students through the API', function (): void {
    $questions = Question::factory()->count(2)->create(['level_id' => $this->level->id]);

    Livewire::test(CreateExam::class)
        ->fillForm(examData(['title' => 'Zichtbaar examen', 'examQuestions' => [
            ['question_id' => $questions[0]->id, 'points' => 1],
            ['question_id' => $questions[1]->id, 'points' => 2],
        ]]))
        ->call('create')
        ->assertHasNoFormErrors();
    $exam = Exam::firstWhere('title', 'Zichtbaar examen');

    $student = User::factory()->create();
    Sanctum::actingAs($student);

    $this->getJson('/api/v1/exams')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Zichtbaar examen')
        ->assertJsonPath('data.0.question_count', 2)
        ->assertJsonPath('data.0.total_points', 3);

    $this->postJson("/api/v1/exams/{$exam->id}/start")
        ->assertCreated()
        ->assertJsonPath('data.questions.0.id', $questions[0]->id)
        ->assertJsonPath('data.questions.1.points', 2);
});
