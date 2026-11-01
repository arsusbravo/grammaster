<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates an administrator who can open the admin panel', function () {
    $this->artisan('app:create-admin', ['email' => 'beheer@example.nl', '--name' => 'Beheerder', '--password' => 'geheim1234'])
        ->expectsOutputToContain('Created administrator')
        ->assertSuccessful();

    $user = User::where('email', 'beheer@example.nl')->firstOrFail();

    expect($user->is_admin)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('geheim1234', $user->password))->toBeTrue();
});

it('promotes an existing student and keeps their password when none is given', function () {
    $student = User::factory()->create(['email' => 'leerling@example.nl', 'password' => 'oud-wachtwoord']);

    $this->artisan('app:create-admin', ['email' => 'leerling@example.nl'])
        ->expectsConfirmation('Set a new password?', 'no')
        ->expectsOutputToContain('Updated administrator')
        ->assertSuccessful();

    $student->refresh();

    expect($student->is_admin)->toBeTrue()
        ->and(Hash::check('oud-wachtwoord', $student->password))->toBeTrue();
    expect(User::count())->toBe(1);
});

it('refuses a short password and creates nothing', function () {
    $this->artisan('app:create-admin', ['email' => 'beheer@example.nl', '--name' => 'Beheerder', '--password' => 'kort'])
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('refuses an invalid e-mail address', function () {
    $this->artisan('app:create-admin', ['email' => 'geen-email', '--name' => 'Beheerder', '--password' => 'geheim1234'])
        ->assertFailed();

    expect(User::count())->toBe(0);
});
