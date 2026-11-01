<?php

declare(strict_types=1);

use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Admins\Pages\EditAdmin;
use App\Filament\Resources\Admins\Pages\ListAdmins;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\RelationManagers\AttemptsRelationManager;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Level;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\Pages\Login;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create(['name' => 'Hoofdbeheerder', 'email' => 'hoofd@example.nl']);
    $this->actingAs($this->admin);
    Filament\Facades\Filament::setCurrentPanel('admin');
});

describe('students: list', function (): void {
    it('shows students only, with their level and number of attempts', function (): void {
        $level = Level::factory()->create(['name' => 'HAVO']);
        $student = User::factory()->create(['name' => 'Sanne', 'level_id' => $level->id]);
        Attempt::factory()->count(2)->for($student)->create();

        Livewire::test(ListStudents::class)
            ->assertCanSeeTableRecords([$student])
            ->assertCanNotSeeTableRecords([$this->admin])
            ->assertSee('Sanne')
            ->assertSee('HAVO')
            ->assertSee('2 pogingen');
    });

    it('says "poging" for one attempt', function (): void {
        $student = User::factory()->create();
        Attempt::factory()->for($student)->create();

        Livewire::test(ListStudents::class)->assertSee('1 poging')->assertDontSee('1 pogingen');
    });

    it('searches on name and e-mail and filters on level and activity', function (): void {
        $level = Level::factory()->create();
        $a = User::factory()->create(['name' => 'Alpha Leerling', 'email' => 'alpha@example.nl', 'level_id' => $level->id]);
        $b = User::factory()->create(['name' => 'Beta Leerling', 'email' => 'beta@example.nl']);
        Attempt::factory()->for($a)->create();

        Livewire::test(ListStudents::class)
            ->searchTable('beta@')
            ->assertCanSeeTableRecords([$b])->assertCanNotSeeTableRecords([$a])
            ->searchTable('')
            ->filterTable('level_id', $level->id)
            ->assertCanSeeTableRecords([$a])->assertCanNotSeeTableRecords([$b])
            ->removeTableFilters()
            ->filterTable('active', true)
            ->assertCanSeeTableRecords([$a])->assertCanNotSeeTableRecords([$b])
            ->removeTableFilters()
            ->filterTable('active', false)
            ->assertCanSeeTableRecords([$b])->assertCanNotSeeTableRecords([$a]);
    });

    it('does not open an administrator as a student', function (): void {
        $this->get("/admin/students/{$this->admin->id}/edit")->assertNotFound();
    });
});

describe('students: create and edit', function (): void {
    it('creates a student who can sign in to the app', function (): void {
        $level = Level::factory()->create();

        Livewire::test(CreateStudent::class)
            ->fillForm(['name' => 'Nieuwe Leerling', 'email' => 'nieuw@example.nl', 'password' => 'geheim1234', 'level_id' => $level->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $student = User::firstWhere('email', 'nieuw@example.nl');
        expect($student->is_admin)->toBeFalse()
            ->and($student->level_id)->toBe($level->id)
            ->and($student->email_verified_at)->not->toBeNull()
            ->and(Hash::check('geheim1234', $student->password))->toBeTrue()
            ->and($student->password)->not->toBe('geheim1234');

        $this->postJson('/api/v1/auth/login', ['email' => 'nieuw@example.nl', 'password' => 'geheim1234'])
            ->assertOk()->assertJsonPath('data.user.name', 'Nieuwe Leerling');
    });

    it('does not let a student into the admin', function (): void {
        Livewire::test(CreateStudent::class)
            ->fillForm(['name' => 'X', 'email' => 'x@example.nl', 'password' => 'geheim1234'])
            ->call('create');

        expect(User::firstWhere('email', 'x@example.nl')->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeFalse();
    });

    it('validates the input', function (array $data, string $field): void {
        User::factory()->create(['email' => 'bezet@example.nl']);

        Livewire::test(CreateStudent::class)
            ->fillForm(['name' => 'Naam', 'email' => 'ok@example.nl', 'password' => 'geheim1234', ...$data])
            ->call('create')
            ->assertHasFormErrors([$field]);

        expect(User::where('email', 'ok@example.nl')->count())->toBe(0);
    })->with([
        'no name' => [['name' => ''], 'name'],
        'no e-mail' => [['email' => ''], 'email'],
        'invalid e-mail' => [['email' => 'geen-email'], 'email'],
        'e-mail taken' => [['email' => 'bezet@example.nl'], 'email'],
        'no password' => [['password' => ''], 'password'],
        'short password' => [['password' => 'kort'], 'password'],
    ]);

    it('edits name, e-mail and level and keeps the password when it is left empty', function (): void {
        $student = User::factory()->create(['password' => 'oudwachtwoord1']);
        $level = Level::factory()->create();

        Livewire::test(EditStudent::class, ['record' => $student->id])
            ->fillForm(['name' => 'Nieuwe Naam', 'email' => 'nieuw@example.nl', 'level_id' => $level->id, 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $student->refresh();
        expect($student->name)->toBe('Nieuwe Naam')
            ->and($student->email)->toBe('nieuw@example.nl')
            ->and($student->level_id)->toBe($level->id)
            ->and(Hash::check('oudwachtwoord1', $student->password))->toBeTrue();
    });

    it('can keep the same e-mail address when editing', function (): void {
        $student = User::factory()->create();

        Livewire::test(EditStudent::class, ['record' => $student->id])
            ->fillForm(['name' => 'Andere naam'])
            ->call('save')
            ->assertHasNoFormErrors();
    });

    it('cannot take over another user\'s e-mail address', function (): void {
        $other = User::factory()->create();
        $student = User::factory()->create();

        Livewire::test(EditStudent::class, ['record' => $student->id])
            ->fillForm(['email' => $other->email])
            ->call('save')
            ->assertHasFormErrors(['email']);
    });

    it('resets the password and signs the student out everywhere', function (): void {
        $student = User::factory()->create(['password' => 'oudwachtwoord1']);
        $student->createToken('spa');
        $student->createToken('spa');

        Livewire::test(EditStudent::class, ['record' => $student->id])
            ->fillForm(['password' => 'nieuwwachtwoord1'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect(Hash::check('nieuwwachtwoord1', $student->fresh()->password))->toBeTrue()
            ->and($student->tokens()->count())->toBe(0);
        $this->postJson('/api/v1/auth/login', ['email' => $student->email, 'password' => 'nieuwwachtwoord1'])->assertOk();
    });

    it('does not sign the student out when only the name changed', function (): void {
        $student = User::factory()->create();
        $student->createToken('spa');

        Livewire::test(EditStudent::class, ['record' => $student->id])->fillForm(['name' => 'Nieuwe naam'])->call('save');

        expect($student->tokens()->count())->toBe(1);
    });
});

describe('students: attempts and deleting', function (): void {
    it('lists the student\'s own attempts on the edit page', function (): void {
        $student = User::factory()->create();
        $mine = Attempt::factory()->for($student)->finished(80)->create();
        $other = Attempt::factory()->finished(10)->create();

        Livewire::test(AttemptsRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other])
            ->assertSee('80%')
            ->assertSee('Oefenen');
    });

    it('shows exam attempts with their exam and an unfinished one as busy', function (): void {
        $student = User::factory()->create();
        $exam = examWithQuestions(1, 1, ['title' => 'Oefenexamen X']);
        Attempt::factory()->exam($exam)->for($student)->create();

        Livewire::test(AttemptsRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
            ->assertSee('Oefenexamen X')
            ->assertSee('bezig')
            ->assertSee('Examen');
    });

    it('is read-only: no create, edit or delete of attempts', function (): void {
        $student = User::factory()->create();
        Attempt::factory()->for($student)->create();

        Livewire::test(AttemptsRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
            ->assertTableActionDoesNotExist('delete')
            ->assertTableActionDoesNotExist('edit');
    });

    it('deletes a student with their results and API tokens', function (): void {
        $student = User::factory()->create();
        $student->createToken('spa');
        $attempt = Attempt::factory()->for($student)->create();
        AttemptAnswer::factory()->for($attempt)->create();

        Livewire::test(ListStudents::class)->callAction(TestAction::make(DeleteAction::class)->table($student));

        expect(User::find($student->id))->toBeNull()
            ->and(Attempt::count())->toBe(0)
            ->and(AttemptAnswer::count())->toBe(0)
            ->and(DB::table('personal_access_tokens')->count())->toBe(0);
    });

    it('deletes from the edit page too, and in bulk', function (): void {
        $one = User::factory()->create();
        Livewire::test(EditStudent::class, ['record' => $one->id])->callAction(DeleteAction::class);
        expect(User::find($one->id))->toBeNull();

        $many = User::factory()->count(3)->create();
        Livewire::test(ListStudents::class)
            ->selectTableRecords($many->pluck('id')->all())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified('3 verwijderd');

        expect(User::where('is_admin', false)->count())->toBe(0)->and(User::find($this->admin->id))->not->toBeNull();
    });

    it('turns a student into an administrator', function (): void {
        $student = User::factory()->create(['name' => 'Promotie']);
        $student->createToken('spa');

        Livewire::test(ListStudents::class)
            ->callAction(TestAction::make('makeAdmin')->table($student))
            ->assertNotified('Promotie is nu beheerder');

        $student->refresh();
        expect($student->is_admin)->toBeTrue()
            ->and($student->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeTrue()
            ->and($student->tokens()->count())->toBe(0);
        Livewire::test(ListStudents::class)->assertCanNotSeeTableRecords([$student]);
        Livewire::test(ListAdmins::class)->assertCanSeeTableRecords([$student]);
    });
});

describe('administrators', function (): void {
    it('lists administrators only and marks the signed-in one', function (): void {
        $other = User::factory()->admin()->create(['name' => 'Tweede Beheerder']);
        $student = User::factory()->create();

        Livewire::test(ListAdmins::class)
            ->assertCanSeeTableRecords([$this->admin, $other])
            ->assertCanNotSeeTableRecords([$student])
            ->assertSee('Jij');
    });

    it('creates an administrator who can open the admin', function (): void {
        Livewire::test(CreateAdmin::class)
            ->fillForm(['name' => 'Nieuwe Beheerder', 'email' => 'beheer@example.nl', 'password' => 'geheim1234'])
            ->call('create')
            ->assertHasNoFormErrors();

        $new = User::firstWhere('email', 'beheer@example.nl');
        expect($new->is_admin)->toBeTrue()
            ->and($new->level_id)->toBeNull()
            ->and($new->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeTrue()
            ->and(Hash::check('geheim1234', $new->password))->toBeTrue();
    });

    it('lets the new administrator sign in through the admin login form', function (): void {
        Livewire::test(CreateAdmin::class)
            ->fillForm(['name' => 'Nieuwe Beheerder', 'email' => 'beheer@example.nl', 'password' => 'geheim1234'])
            ->call('create');
        auth()->logout();

        Livewire::test(Login::class)
            ->fillForm(['email' => 'beheer@example.nl', 'password' => 'geheim1234'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs(User::firstWhere('email', 'beheer@example.nl'));
    });

    it('validates the input', function (array $data, string $field): void {
        Livewire::test(CreateAdmin::class)
            ->fillForm(['name' => 'Naam', 'email' => 'ok@example.nl', 'password' => 'geheim1234', ...$data])
            ->call('create')
            ->assertHasFormErrors([$field]);
    })->with([
        'no name' => [['name' => ''], 'name'],
        'e-mail taken' => [['email' => 'hoofd@example.nl'], 'email'],
        'no password' => [['password' => ''], 'password'],
        'short password' => [['password' => 'kort'], 'password'],
    ]);

    it('edits another administrator, including their password', function (): void {
        $other = User::factory()->admin()->create(['password' => 'oudwachtwoord1']);

        Livewire::test(EditAdmin::class, ['record' => $other->id])
            ->fillForm(['name' => 'Aangepast', 'password' => 'nieuwwachtwoord1'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($other->fresh()->name)->toBe('Aangepast')
            ->and(Hash::check('nieuwwachtwoord1', $other->fresh()->password))->toBeTrue()
            ->and($other->fresh()->is_admin)->toBeTrue();
    });

    it('stays signed in after changing your own password', function (): void {
        $this->get('/admin')->assertOk();

        Livewire::test(EditAdmin::class, ['record' => $this->admin->id])
            ->fillForm(['password' => 'nieuwwachtwoord1'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/admin')->assertOk();
        expect(Hash::check('nieuwwachtwoord1', $this->admin->fresh()->password))->toBeTrue();
    });

    it('deletes another administrator', function (): void {
        $other = User::factory()->admin()->create();

        Livewire::test(ListAdmins::class)->callAction(TestAction::make(DeleteAction::class)->table($other));

        expect(User::find($other->id))->toBeNull();
    });

    it('never lets an administrator remove or demote themselves', function (): void {
        User::factory()->admin()->create(); // a second admin exists, so only "yourself" is the reason

        Livewire::test(ListAdmins::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($this->admin))
            ->assertNotified('Kan niet worden verwijderd')
            ->callAction(TestAction::make('makeStudent')->table($this->admin))
            ->assertNotified('Dat kan niet');

        expect($this->admin->fresh()->is_admin)->toBeTrue()->and(User::find($this->admin->id))->not->toBeNull();
    });

    it('cannot delete yourself from the edit page either', function (): void {
        User::factory()->admin()->create();

        Livewire::test(EditAdmin::class, ['record' => $this->admin->id])->callAction(DeleteAction::class);

        expect(User::find($this->admin->id))->not->toBeNull();
    });

    it('always keeps at least one administrator', function (): void {
        $other = User::factory()->admin()->create();
        // The signed-in admin is $other now; the only other administrator is $this->admin.
        $this->actingAs($other);
        $this->admin->delete();

        Livewire::test(ListAdmins::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($other))
            ->assertNotified('Kan niet worden verwijderd');

        expect(User::where('is_admin', true)->count())->toBe(1);
    });

    it('turns another administrator into a student', function (): void {
        $other = User::factory()->admin()->create(['name' => 'Terug naar leerling']);

        Livewire::test(ListAdmins::class)
            ->callAction(TestAction::make('makeStudent')->table($other))
            ->assertNotified('Terug naar leerling is nu een leerling');

        expect($other->fresh()->is_admin)->toBeFalse()
            ->and($other->fresh()->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeFalse();
        Livewire::test(ListStudents::class)->assertCanSeeTableRecords([$other]);
    });

    it('bulk delete skips the signed-in administrator', function (): void {
        $others = User::factory()->admin()->count(2)->create();

        Livewire::test(ListAdmins::class)
            ->selectTableRecords([$this->admin->id, ...$others->pluck('id')->all()])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified('2 verwijderd');

        expect(User::where('is_admin', true)->pluck('id')->all())->toBe([$this->admin->id]);
    });
});

describe('navigation', function (): void {
    it('has a logout item in the sidebar that signs the admin out', function (): void {
        $html = $this->get('/admin')->assertOk()->getContent();

        expect($html)->toContain('Uitloggen')
            ->toMatch('#<form[^>]+method="POST"[^>]+action="[^"]*/admin/logout"#')
            ->toContain('Gebruikers')
            ->toContain('Leerlingen')
            ->toContain('Beheerders');

        $this->post('/admin/logout')->assertRedirect();
        $this->assertGuest();
    });

    it('shows the number of students next to the menu item', function (): void {
        User::factory()->count(4)->create();

        expect(StudentResource::getNavigationBadge())->toBe('4')
            ->and($this->get('/admin')->getContent())->toContain('fi-badge');
    });
});
