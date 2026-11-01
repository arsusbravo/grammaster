<?php

declare(strict_types=1);

use App\Filament\Resources\Levels\Pages\ManageLevels;
use App\Filament\Resources\Topics\Pages\ManageTopics;
use App\Models\Exam;
use App\Models\Level;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->create());
    Filament\Facades\Filament::setCurrentPanel('admin');
});

describe('levels', function (): void {
    it('lists levels with their usage', function (): void {
        $level = Level::factory()->create(['name' => 'HAVO']);
        Question::factory()->count(2)->create(['level_id' => $level->id]);

        Livewire::test(ManageLevels::class)
            ->assertCanSeeTableRecords([$level])
            ->assertSee('HAVO');
    });

    it('creates a level at the end of the list and fills the code from the name', function (): void {
        Level::factory()->create(['sort_order' => 5]);

        Livewire::test(ManageLevels::class)
            ->callAction(CreateAction::class, ['name' => 'Gymnasium', 'slug' => 'gymnasium'])
            ->assertHasNoFormErrors();

        $level = Level::firstWhere('slug', 'gymnasium');
        expect($level->name)->toBe('Gymnasium')->and($level->sort_order)->toBe(6);
    });

    it('validates the code', function (array $data): void {
        Level::factory()->create(['slug' => 'vmbo']);

        Livewire::test(ManageLevels::class)
            ->callAction(CreateAction::class, $data)
            ->assertHasFormErrors(['slug']);
    })->with([
        'duplicate' => [['name' => 'Anders', 'slug' => 'vmbo']],
        'capitals and spaces' => [['name' => 'Anders', 'slug' => 'Mijn Niveau']],
    ]);

    it('edits a level', function (): void {
        $level = Level::factory()->create(['name' => 'Oud']);

        Livewire::test(ManageLevels::class)
            ->callAction(TestAction::make('edit')->table($level), ['name' => 'Nieuw', 'slug' => $level->slug])
            ->assertHasNoFormErrors();

        expect($level->fresh()->name)->toBe('Nieuw');
    });

    it('deletes an unused level', function (): void {
        $level = Level::factory()->create();

        Livewire::test(ManageLevels::class)->callAction(TestAction::make(DeleteAction::class)->table($level));

        expect(Level::count())->toBe(0);
    });

    it('refuses to delete a level that still has questions or exams and explains why', function (): void {
        $withQuestion = Level::factory()->create(['name' => 'HAVO']);
        Question::factory()->create(['level_id' => $withQuestion->id]);
        $withExam = Level::factory()->create();
        Exam::factory()->create(['level_id' => $withExam->id]);

        Livewire::test(ManageLevels::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($withQuestion))
            ->assertNotified('Kan niet worden verwijderd')
            ->callAction(TestAction::make(DeleteAction::class)->table($withExam));

        expect(Level::count())->toBe(2);
    });

    it('bulk deletes only the unused levels', function (): void {
        $used = Level::factory()->create();
        Question::factory()->create(['level_id' => $used->id]);
        $unused = Level::factory()->count(2)->create();

        Livewire::test(ManageLevels::class)
            ->selectTableRecords([$used->id, ...$unused->pluck('id')->all()])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified('2 verwijderd');

        expect(Level::pluck('id')->all())->toBe([$used->id]);
    });
});

describe('topics', function (): void {
    it('creates and edits a topic', function (): void {
        Livewire::test(ManageTopics::class)
            ->callAction(CreateAction::class, ['name' => 'Voegwoorden', 'slug' => 'voegwoorden', 'description' => 'Als, dan, want.'])
            ->assertHasNoFormErrors();

        $topic = Topic::firstWhere('slug', 'voegwoorden');
        expect($topic->description)->toBe('Als, dan, want.');

        Livewire::test(ManageTopics::class)
            ->callAction(TestAction::make('edit')->table($topic), ['name' => 'Voegwoorden (als/dan)', 'slug' => 'voegwoorden', 'description' => 'x'])
            ->assertHasNoFormErrors();

        expect($topic->fresh()->name)->toBe('Voegwoorden (als/dan)');
    });

    it('requires a unique code', function (): void {
        Topic::factory()->create(['slug' => 'lidwoorden']);

        Livewire::test(ManageTopics::class)
            ->callAction(CreateAction::class, ['name' => 'Anders', 'slug' => 'lidwoorden'])
            ->assertHasFormErrors(['slug']);
    });

    it('refuses to delete a topic with questions but deletes an empty one', function (): void {
        $used = Topic::factory()->create();
        Question::factory()->create(['topic_id' => $used->id]);
        $empty = Topic::factory()->create();

        Livewire::test(ManageTopics::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($used))
            ->assertNotified('Kan niet worden verwijderd')
            ->callAction(TestAction::make(DeleteAction::class)->table($empty));

        expect(Topic::pluck('id')->all())->toBe([$used->id]);
    });
});
