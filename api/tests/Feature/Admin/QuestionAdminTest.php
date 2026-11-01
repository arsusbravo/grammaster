<?php

declare(strict_types=1);

use App\Enums\QuestionType;
use App\Filament\Resources\Questions\Pages\CreateQuestion;
use App\Filament\Resources\Questions\Pages\EditQuestion;
use App\Filament\Resources\Questions\Pages\ListQuestions;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Exam;
use App\Models\Level;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
    Filament\Facades\Filament::setCurrentPanel('admin');
    $this->topic = Topic::factory()->create();
    $this->level = Level::factory()->create();
});

/** Create a question through the admin form. */
function createViaForm(array $attributes, array $editor)
{
    return Livewire::test(CreateQuestion::class)
        ->fillForm([
            'topic_id' => test()->topic->id,
            'level_id' => test()->level->id,
            'prompt' => 'Opdracht',
            'explanation' => 'Uitleg bij de vraag.',
            'difficulty' => 2,
            'is_published' => true,
            ...$attributes,
            'editor' => $editor,
        ])
        ->call('create');
}

describe('create', function (): void {
    it('creates every question type and the result works with the real answer checking', function (string $type, array $editor, array $correct, array $wrong): void {
        createViaForm(['type' => $type], $editor)->assertHasNoFormErrors();

        $question = Question::latest('id')->firstOrFail();
        $handler = $question->type->handler();

        expect($question->type->value)->toBe($type)
            ->and($question->is_published)->toBeTrue()
            ->and($question->difficulty)->toBe(2)
            ->and($handler->check($question->data, $correct)->isCorrect)->toBeTrue()
            ->and($handler->check($question->data, $wrong)->isCorrect)->toBeFalse();
    })->with([
        'multiple_choice' => ['multiple_choice',
            ['multiple_choice' => ['options' => [['text' => 'Hij word', 'correct' => false], ['text' => 'Hij wordt', 'correct' => true], ['text' => 'Hij wort', 'correct' => false]]]],
            ['selected' => [1]], ['selected' => [0]]],
        'multiple_choice with two correct' => ['multiple_choice',
            ['multiple_choice' => ['options' => [['text' => 'a', 'correct' => true], ['text' => 'b', 'correct' => true], ['text' => 'c', 'correct' => false]]]],
            ['selected' => [0, 1]], ['selected' => [0]]],
        'fill_blank' => ['fill_blank',
            ['fill_blank' => ['text' => 'Mijn broer {{blank}} hier.', 'blanks' => [['accepted' => ['werkt', 'werkt.'], 'hint' => 'werken']], 'case_sensitive' => false, 'ignore_extra_whitespace' => true]],
            ['blanks' => ['WERKT ']], ['blanks' => ['werk']]],
        'order_words' => ['order_words',
            ['order_words' => ['sentence' => 'Gisteren ging ik zwemmen', 'alternatives' => 'Ik ging gisteren zwemmen', 'case_sensitive' => false]],
            ['order' => ['ik', 'ging', 'gisteren', 'zwemmen']], ['order' => ['ging', 'ik', 'gisteren', 'zwemmen']]],
        'mark_error' => ['mark_error',
            ['mark_error' => ['sentence' => 'Wij *fietst* naar school', 'correction' => 'fietsen']],
            ['index' => 1], ['index' => 0]],
        'parse_sentence' => ['parse_sentence',
            ['parse_sentence' => ['roles' => ['onderwerp', 'persoonsvorm'], 'tokens' => [['text' => 'De kat', 'role' => 'onderwerp'], ['text' => 'eet', 'role' => 'persoonsvorm']]]],
            ['roles' => ['onderwerp', 'persoonsvorm']], ['roles' => ['persoonsvorm', 'onderwerp']]],
    ]);

    it('only stores the fields of the chosen type', function (): void {
        createViaForm(['type' => 'mark_error'], [
            'mark_error' => ['sentence' => 'Wij *fietst* naar school', 'correction' => 'fietsen'],
            'multiple_choice' => ['options' => [['text' => 'overblijfsel', 'correct' => true]]],
        ])->assertHasNoFormErrors();

        $question = Question::latest('id')->first();
        expect(array_keys($question->data))->toEqualCanonicalizing(['tokens', 'error_index', 'correction'])
            ->and($question->getAttributes())->not->toHaveKey('editor');
    });

    it('requires the general fields', function (): void {
        Livewire::test(CreateQuestion::class)
            ->fillForm(['topic_id' => null, 'level_id' => null, 'type' => null, 'prompt' => '', 'explanation' => ''])
            ->call('create')
            ->assertHasFormErrors(['topic_id' => 'required', 'level_id' => 'required', 'type' => 'required', 'prompt' => 'required', 'explanation' => 'required']);

        expect(Question::count())->toBe(0);
    });

    it('catches an unusable choice with field validation', function (string $type, array $editor): void {
        createViaForm(['type' => $type], $editor)->assertHasFormErrors();

        expect(Question::count())->toBe(0);
    })->with([
        'no correct option' => ['multiple_choice', ['multiple_choice' => ['options' => [['text' => 'a', 'correct' => false], ['text' => 'b', 'correct' => false]]]]],
        'role that is not offered' => ['parse_sentence', ['parse_sentence' => ['roles' => ['onderwerp', 'persoonsvorm'], 'tokens' => [['text' => 'a', 'role' => 'onderwerp'], ['text' => 'b', 'role' => 'lijdend voorwerp']]]]],
    ]);

    it('does not save and explains the problem when the question content is incomplete', function (string $type, array $editor, string $message): void {
        createViaForm(['type' => $type], $editor)
            ->assertNotified('De vraag kan nog niet worden opgeslagen');

        expect(Question::count())->toBe(0);
    })->with([
        'blank marker missing' => ['fill_blank', ['fill_blank' => ['text' => 'geen gat', 'blanks' => [['accepted' => ['x']]]]], '{{blank}}'],
        'two markers one blank' => ['fill_blank', ['fill_blank' => ['text' => '{{blank}} en {{blank}}', 'blanks' => [['accepted' => ['x']]]]], 'invulvelden'],
        'alternative with other words' => ['order_words', ['order_words' => ['sentence' => 'a b c', 'alternatives' => 'a b d']], 'dezelfde woorden'],
        'no starred word' => ['mark_error', ['mark_error' => ['sentence' => 'Wij fietst naar school', 'correction' => 'fietsen']], 'sterretjes'],
    ]);

    it('shows the Dutch problems in the notification', function (): void {
        createViaForm(['type' => 'mark_error'], ['mark_error' => ['sentence' => 'Wij fietst naar school', 'correction' => 'fietsen']])
            ->assertNotified(Notification::make()->danger()
                ->title('De vraag kan nog niet worden opgeslagen')
                ->body('Zet sterretjes om precies één fout woord, bijvoorbeeld: Wij *fietst* naar school.')
                ->persistent());
    });
});

describe('edit', function (): void {
    beforeEach(fn () => $this->seed(DatabaseSeeder::class));

    it('loads the friendly fields for each type', function (): void {
        $mark = Question::where('type', QuestionType::MarkError)->first();
        Livewire::test(EditQuestion::class, ['record' => $mark->id])
            ->assertFormSet([
                'type' => QuestionType::MarkError,
                'prompt' => $mark->prompt,
                'editor.mark_error.sentence' => implode(' ', array_map(
                    fn ($t, $i) => $i === $mark->data['error_index'] ? "*{$t}*" : $t, $mark->data['tokens'], array_keys($mark->data['tokens']),
                )),
                'editor.mark_error.correction' => $mark->data['correction'],
            ]);

        $mc = Question::where('type', QuestionType::MultipleChoice)->first();
        Livewire::test(EditQuestion::class, ['record' => $mc->id])
            ->assertFormSet(['type' => QuestionType::MultipleChoice, 'is_published' => true]);
    });

    it('saves every seeded question unchanged', function (): void {
        foreach (Question::all() as $question) {
            $handler = $question->type->handler();
            $expected = $handler->correctAnswer($question->data);

            Livewire::test(EditQuestion::class, ['record' => $question->id])
                ->call('save')
                ->assertHasNoFormErrors();

            $saved = $question->fresh();
            expect($handler->correctAnswer($saved->data))->toEqual($expected, "answer of #{$question->id}")
                ->and($handler->check($saved->data, $expected)->isCorrect)->toBeTrue()
                ->and($saved->prompt)->toBe($question->prompt)
                ->and($saved->explanation)->toBe($question->explanation);
        }
    });

    it('updates the content of a question', function (): void {
        $question = Question::where('type', QuestionType::MarkError)->first();

        Livewire::test(EditQuestion::class, ['record' => $question->id])
            ->fillForm([
                'prompt' => 'Nieuwe opdracht',
                'difficulty' => 4,
                'editor' => ['mark_error' => ['sentence' => 'Zij *werkt* hier', 'correction' => 'werken']],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $question->refresh();
        expect($question->prompt)->toBe('Nieuwe opdracht')
            ->and($question->difficulty)->toBe(4)
            ->and($question->data)->toEqual(['tokens' => ['Zij', 'werkt', 'hier'], 'error_index' => 1, 'correction' => 'werken']);
    });

    it('keeps the old data when the new content is invalid', function (): void {
        $question = Question::where('type', QuestionType::MarkError)->first();
        $before = $question->data;

        Livewire::test(EditQuestion::class, ['record' => $question->id])
            ->fillForm(['editor' => ['mark_error' => ['sentence' => 'Geen sterretjes hier', 'correction' => 'x']]])
            ->call('save')
            ->assertNotified('De vraag kan nog niet worden opgeslagen');

        expect($question->fresh()->data)->toEqual($before);
    });

    it('can change the type of a question', function (): void {
        $question = Question::where('type', QuestionType::MarkError)->first();

        Livewire::test(EditQuestion::class, ['record' => $question->id])
            ->fillForm([
                'type' => 'multiple_choice',
                'editor' => ['multiple_choice' => ['options' => [['text' => 'a', 'correct' => true], ['text' => 'b', 'correct' => false]]]],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($question->fresh()->type)->toBe(QuestionType::MultipleChoice)
            ->and($question->fresh()->data)->toEqual(['options' => ['a', 'b'], 'correct' => [0]]);
    });

    it('does not let a question in a published exam go back to draft', function (): void {
        $exam = Factory::factoryForModel(Exam::class)->create();
        $question = Question::factory()->create();
        $exam->questions()->attach($question->id);

        Livewire::test(EditQuestion::class, ['record' => $question->id])
            ->fillForm(['is_published' => false])
            ->call('save')
            ->assertNotified('Deze vraag blijft gepubliceerd');

        expect($question->fresh()->is_published)->toBeTrue();

        $exam->update(['is_published' => false]);
        Livewire::test(EditQuestion::class, ['record' => $question->id])
            ->fillForm(['is_published' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($question->fresh()->is_published)->toBeFalse();
    });
});

describe('list', function (): void {
    it('shows the questions with a readable type and supports search and filters', function (): void {
        $a = Question::factory()->create(['prompt' => 'Alpha vraag over lidwoorden']);
        $b = Question::factory()->markError()->create(['prompt' => 'Beta vraag over fouten', 'is_published' => false]);

        Livewire::test(ListQuestions::class)
            ->assertCanSeeTableRecords([$a, $b])
            ->assertSee('Meerkeuze')
            ->assertSee('Fout aanwijzen')
            ->searchTable('Alpha')
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b])
            ->searchTable('')
            ->filterTable('type', 'mark_error')
            ->assertCanSeeTableRecords([$b])
            ->assertCanNotSeeTableRecords([$a])
            ->removeTableFilters()
            ->filterTable('is_published', true)
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b])
            ->removeTableFilters()
            ->filterTable('topic_id', $a->topic_id)
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b]);
    });

    it('shows the question content and finds questions by it', function (): void {
        $a = Question::factory()->markError()->create(['prompt' => 'Tik op het foute woord.']);
        $b = Question::factory()->create(['prompt' => 'Tik op het foute woord.']);

        Livewire::test(ListQuestions::class)
            ->assertSee('Wij fietst naar school')
            ->assertSee('Hij word boos. / Hij wordt boos. / Hij wort boos.')
            ->searchTable('fietst')
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b]);
    });

    it('publishes and unpublishes from the list', function (): void {
        $question = Question::factory()->unpublished()->create();

        Livewire::test(ListQuestions::class)->callAction(TestAction::make('togglePublished')->table($question));
        expect($question->fresh()->is_published)->toBeTrue();

        Livewire::test(ListQuestions::class)->callAction(TestAction::make('togglePublished')->table($question));
        expect($question->fresh()->is_published)->toBeFalse();
    });

    it('keeps a question published while a published exam uses it', function (): void {
        $exam = examWithQuestions(1);
        $question = $exam->questions->first();

        Livewire::test(ListQuestions::class)
            ->callAction(TestAction::make('togglePublished')->table($question))
            ->assertNotified('Deze vraag blijft gepubliceerd');

        expect($question->fresh()->is_published)->toBeTrue();
    });

    it('bulk publishes and unpublishes, skipping questions that a published exam needs', function (): void {
        $drafts = Question::factory()->unpublished()->count(2)->create();
        Livewire::test(ListQuestions::class)
            ->selectTableRecords($drafts->pluck('id')->all())
            ->callAction(TestAction::make('publish')->table()->bulk());
        expect(Question::published()->count())->toBe(2);

        $exam = examWithQuestions(1);
        $locked = $exam->questions->first();
        $free = Question::factory()->create();

        Livewire::test(ListQuestions::class)
            ->selectTableRecords([$locked->id, $free->id])
            ->callAction(TestAction::make('unpublish')->table()->bulk())
            ->assertNotified('Deze vraag blijft gepubliceerd');

        expect($locked->fresh()->is_published)->toBeTrue()->and($free->fresh()->is_published)->toBeFalse();
    });

    it('duplicates a question as a draft', function (): void {
        $question = Question::factory()->markError()->create(['prompt' => 'Origineel']);

        Livewire::test(ListQuestions::class)->callAction(TestAction::make('replicate')->table($question));

        $copy = Question::where('id', '!=', $question->id)->first();
        expect(Question::count())->toBe(2)
            ->and($copy->prompt)->toBe('Origineel')
            ->and($copy->data)->toEqual($question->data)
            ->and($copy->is_published)->toBeFalse();
    });

    it('deletes an unused question', function (): void {
        $question = Question::factory()->create();

        Livewire::test(ListQuestions::class)->callAction(TestAction::make(DeleteAction::class)->table($question));

        expect(Question::count())->toBe(0);
    });

    it('refuses to delete a question students have answered or an exam uses', function (): void {
        $answered = AttemptAnswer::factory()->for(Attempt::factory())->create()->question;
        $inExam = examWithQuestions(1)->questions->first();

        Livewire::test(ListQuestions::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($answered))
            ->assertNotified('Kan niet worden verwijderd')
            ->callAction(TestAction::make(DeleteAction::class)->table($inExam))
            ->assertNotified('Kan niet worden verwijderd');

        expect(Question::whereKey([$answered->id, $inExam->id])->count())->toBe(2);
    });

    it('bulk deletes only what is safe', function (): void {
        $free = Question::factory()->count(2)->create();
        $inExam = examWithQuestions(1)->questions->first();

        Livewire::test(ListQuestions::class)
            ->selectTableRecords([...$free->pluck('id')->all(), $inExam->id])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified('2 verwijderd');

        expect(Question::whereKey($inExam->id)->exists())->toBeTrue()
            ->and(Question::whereIn('id', $free->pluck('id'))->count())->toBe(0);
    });
});
