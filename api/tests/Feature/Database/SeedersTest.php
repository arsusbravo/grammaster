<?php

declare(strict_types=1);

use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\Level;
use App\Models\Passage;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Support\QuestionData;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('seeds all levels and topics', function (): void {
    expect(Level::orderBy('sort_order')->pluck('slug')->all())->toBe(['vmbo', 'mavo', 'havo', 'vwo', 'university'])
        ->and(Topic::pluck('slug')->all())->toEqualCanonicalizing([
            'werkwoordspelling', 'lidwoorden', 'voornaamwoorden', 'zinsontleding',
            'samengestelde-woorden', 'hoofdletters', 'leestekens', 'tekstbegrip',
        ]);
});

it('seeds at least five valid, published questions per type', function (QuestionType $type): void {
    $questions = Question::where('type', $type)->get();

    expect($questions->count())->toBeGreaterThanOrEqual(5)
        ->and($questions->pluck('level_id')->unique()->count())->toBeGreaterThanOrEqual(4);

    foreach ($questions as $question) {
        QuestionData::validate($type, $question->data);
        expect($question->is_published)->toBeTrue()
            ->and(mb_strlen($question->explanation))->toBeGreaterThan(40)
            ->and($question->difficulty)->toBeBetween(1, 5);
    }
})->with(array_map(fn (QuestionType $t) => [$t], QuestionType::cases()));

it('seeds three texts with valid, published questions about them', function (): void {
    $passages = Passage::with('questions')->get();

    expect($passages)->toHaveCount(3);

    foreach ($passages as $passage) {
        expect($passage->questions->count())->toBeGreaterThanOrEqual(3)
            ->and(mb_strlen($passage->body))->toBeGreaterThan(300)
            ->and(substr_count($passage->body, "\n\n"))->toBeGreaterThanOrEqual(2); // several paragraphs

        foreach ($passage->questions as $question) {
            QuestionData::validate($question->type, $question->data);
            expect($question->is_published)->toBeTrue()
                ->and($question->topic->slug)->toBe('tekstbegrip')
                ->and(mb_strlen($question->explanation))->toBeGreaterThan(40);
        }
    }

    // One text per school level, as intended.
    expect($passages->flatMap(fn ($p) => $p->questions->pluck('level.slug'))->unique()->sort()->values()->all())->toBe(['havo', 'vmbo', 'vwo']);
});

it('keeps questions about a text out of the sample exams', function (): void {
    expect(Exam::all()->flatMap->questions->whereNotNull('passage_id'))->toHaveCount(0);
});

it('uses every topic at least once', function (): void {
    expect(Topic::whereDoesntHave('questions')->count())->toBe(0);
});

it('seeds a sample exam for vmbo and vwo built from that level', function (string $slug): void {
    $exam = Exam::whereHas('level', fn ($q) => $q->where('slug', $slug))->firstOrFail();

    expect($exam->is_published)->toBeTrue()
        ->and($exam->questions->count())->toBeGreaterThanOrEqual(8)
        ->and($exam->questions->pluck('level_id')->unique()->all())->toBe([$exam->level_id])
        ->and($exam->totalPoints())->toBeGreaterThan(0);
})->with(['vmbo', 'vwo']);

it('is idempotent', function (): void {
    $counts = [Question::count(), Exam::count(), User::count(), Topic::count(), Passage::count()];

    $this->seed(DatabaseSeeder::class);

    expect([Question::count(), Exam::count(), User::count(), Topic::count(), Passage::count()])->toBe($counts)
        ->and(Exam::first()->questions()->count())->toBe(Exam::first()->questions->count());
});

it('creates the admin and the test user', function (): void {
    $admin = User::where('email', 'info@arsus.nl')->firstOrFail();
    $student = User::where('email', 'test@arsus.nl')->firstOrFail();

    expect($admin->is_admin)->toBeTrue()
        ->and(Hash::check('admin1234', $admin->password))->toBeTrue()
        ->and($student->is_admin)->toBeFalse()
        ->and(Hash::check('test1234', $student->password))->toBeTrue()
        ->and($student->level->slug)->toBe('havo');
});

it('lets only the admin into the Filament panel', function (): void {
    $panel = Filament::getPanel('admin');

    expect(User::where('email', 'info@arsus.nl')->first()->canAccessPanel($panel))->toBeTrue()
        ->and(User::where('email', 'test@arsus.nl')->first()->canAccessPanel($panel))->toBeFalse();
});

it('does not create the default users in production', function (): void {
    User::query()->delete();
    app()->detectEnvironment(fn () => 'production');

    app(UserSeeder::class)->run();

    expect(User::count())->toBe(0);
});
