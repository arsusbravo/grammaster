<?php

declare(strict_types=1);

use App\Filament\Resources\Passages\Pages\CreatePassage;
use App\Filament\Resources\Passages\Pages\EditPassage;
use App\Filament\Resources\Passages\Pages\ListPassages;
use App\Filament\Resources\Questions\Pages\CreateQuestion;
use App\Models\Level;
use App\Models\Passage;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->create());
    Filament\Facades\Filament::setCurrentPanel('admin');
});

it('lists texts with the number of questions', function (): void {
    $passage = Passage::factory()->create(['title' => 'De verloren sleutel']);
    Question::factory()->count(2)->withPassage($passage)->create();

    Livewire::test(ListPassages::class)
        ->assertCanSeeTableRecords([$passage])
        ->assertSee('De verloren sleutel')
        ->assertSee('2 vragen');
});

it('creates and edits a text', function (): void {
    Livewire::test(CreatePassage::class)
        ->fillForm(['title' => 'Een dag zonder telefoon', 'body' => "Eerste alinea.\n\nTweede alinea."])
        ->call('create')
        ->assertHasNoFormErrors();

    $passage = Passage::firstWhere('title', 'Een dag zonder telefoon');
    expect($passage->body)->toBe("Eerste alinea.\n\nTweede alinea.");

    Livewire::test(EditPassage::class, ['record' => $passage->id])
        ->fillForm(['title' => 'Een dag offline'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($passage->refresh()->title)->toBe('Een dag offline');
});

it('requires a title and a text', function (): void {
    Livewire::test(CreatePassage::class)
        ->fillForm(['title' => '', 'body' => ''])
        ->call('create')
        ->assertHasFormErrors(['title' => 'required', 'body' => 'required']);
});

it('blocks deleting a text that still has questions and explains why', function (): void {
    $passage = Passage::factory()->create(['title' => 'De verloren sleutel']);
    Question::factory()->withPassage($passage)->create();

    Livewire::test(EditPassage::class, ['record' => $passage->id])
        ->callAction(DeleteAction::class);

    expect(Passage::find($passage->id))->not->toBeNull();
});

it('deletes a text without questions', function (): void {
    $passage = Passage::factory()->create();

    Livewire::test(EditPassage::class, ['record' => $passage->id])
        ->callAction(DeleteAction::class);

    expect(Passage::find($passage->id))->toBeNull();
});

it('links a new question to a text', function (): void {
    $passage = Passage::factory()->create();

    Livewire::test(CreateQuestion::class)
        ->fillForm([
            'topic_id' => Topic::factory()->create()->id,
            'level_id' => Level::factory()->create()->id,
            'passage_id' => $passage->id,
            'type' => 'multiple_choice',
            'prompt' => 'Waar vond Sanne haar sleutel?',
            'explanation' => 'Dat staat in de laatste zin.',
            'difficulty' => 1,
            'is_published' => true,
            'editor' => ['multiple_choice' => ['options' => [['text' => 'In de koelkast', 'correct' => true], ['text' => 'In haar jaszak', 'correct' => false]]]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Question::latest('id')->first()->passage_id)->toBe($passage->id);
});
