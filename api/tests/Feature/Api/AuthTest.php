<?php

declare(strict_types=1);

use App\Models\Level;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('register', function (): void {
    it('creates a student and returns a token', function (): void {
        $level = Level::factory()->create();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Sanne',
            'email' => 'sanne@example.com',
            'password' => 'geheim1234',
            'password_confirmation' => 'geheim1234',
            'level_id' => $level->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'sanne@example.com')
            ->assertJsonPath('data.user.level.id', $level->id)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'level_id']]])
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.is_admin');

        $user = User::firstWhere('email', 'sanne@example.com');
        expect($user->is_admin)->toBeFalse()->and($user->level_id)->toBe($level->id);
    });

    it('works without a level', function (): void {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Sanne', 'email' => 'sanne@example.com',
            'password' => 'geheim1234', 'password_confirmation' => 'geheim1234',
        ])->assertCreated()->assertJsonPath('data.user.level_id', null);
    });

    it('validates input with Dutch messages', function (array $payload, string $field): void {
        User::factory()->create(['email' => 'bezet@example.com']);

        $this->postJson('/api/v1/auth/register', $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
    })->with([
        'missing name' => [['email' => 'a@example.com', 'password' => 'geheim1234', 'password_confirmation' => 'geheim1234'], 'name'],
        'invalid email' => [['name' => 'A', 'email' => 'geen-email', 'password' => 'geheim1234', 'password_confirmation' => 'geheim1234'], 'email'],
        'email taken' => [['name' => 'A', 'email' => 'bezet@example.com', 'password' => 'geheim1234', 'password_confirmation' => 'geheim1234'], 'email'],
        'short password' => [['name' => 'A', 'email' => 'a@example.com', 'password' => 'kort', 'password_confirmation' => 'kort'], 'password'],
        'unconfirmed password' => [['name' => 'A', 'email' => 'a@example.com', 'password' => 'geheim1234', 'password_confirmation' => 'anders'], 'password'],
        'unknown level' => [['name' => 'A', 'email' => 'a@example.com', 'password' => 'geheim1234', 'password_confirmation' => 'geheim1234', 'level_id' => 999], 'level_id'],
    ]);

    it('answers in Dutch', function (): void {
        $this->postJson('/api/v1/auth/register', [])
            ->assertJsonPath('errors.email.0', 'E-mailadres is verplicht.');
    });
});

describe('login', function (): void {
    it('returns a token for valid credentials', function (): void {
        User::factory()->create(['email' => 'sanne@example.com', 'password' => 'geheim1234']);

        $this->postJson('/api/v1/auth/login', ['email' => 'sanne@example.com', 'password' => 'geheim1234'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email']]]);
    });

    it('rejects wrong credentials without saying which part was wrong', function (string $email, string $password): void {
        User::factory()->create(['email' => 'sanne@example.com', 'password' => 'geheim1234']);

        $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => $password])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Deze combinatie van e-mailadres en wachtwoord is niet bekend.');
    })->with([
        'wrong password' => ['sanne@example.com', 'verkeerd'],
        'unknown user' => ['onbekend@example.com', 'geheim1234'],
    ]);

    it('validates the input', function (): void {
        $this->postJson('/api/v1/auth/login', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    });

    it('throttles repeated failures', function (): void {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'fout'])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'fout'])->assertTooManyRequests();
    });
});

describe('logout', function (): void {
    it('revokes the token that was used', function (): void {
        User::factory()->create(['email' => 'sanne@example.com', 'password' => 'geheim1234']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'sanne@example.com', 'password' => 'geheim1234'])
            ->json('data.token');

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
        expect(DB::table('personal_access_tokens')->count())->toBe(0);
    });

    it('requires authentication', function (): void {
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    });
});

describe('me', function (): void {
    it('requires authentication', function (): void {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->patchJson('/api/v1/auth/me', ['name' => 'X'])->assertUnauthorized();
    });

    it('returns the signed in student with their level', function (): void {
        $user = student(User::factory()->forLevel()->create());

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.level.id', $user->level_id);
    });

    it('lets a student change their name and level', function (): void {
        $user = student();
        $level = Level::factory()->create();

        $this->patchJson('/api/v1/auth/me', ['name' => 'Nieuwe naam', 'level_id' => $level->id])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nieuwe naam')
            ->assertJsonPath('data.level.id', $level->id);

        expect($user->fresh()->level_id)->toBe($level->id);
    });

    it('does not let a student make themselves admin or change the e-mail', function (): void {
        $user = student();
        $email = $user->email;

        $this->patchJson('/api/v1/auth/me', ['is_admin' => true, 'email' => 'nieuw@example.com'])->assertOk();

        expect($user->fresh()->is_admin)->toBeFalse()->and($user->fresh()->email)->toBe($email);
    });

    it('rejects an unknown level', function (): void {
        student();

        $this->patchJson('/api/v1/auth/me', ['level_id' => 999])->assertUnprocessable()->assertJsonValidationErrors('level_id');
    });
});

describe('change password', function (): void {
    function passwordPayload(array $overrides = []): array
    {
        return [
            'current_password' => 'oudwachtwoord1',
            'password' => 'nieuwwachtwoord1',
            'password_confirmation' => 'nieuwwachtwoord1',
            ...$overrides,
        ];
    }

    it('requires authentication', function (): void {
        $this->putJson('/api/v1/auth/password', passwordPayload())->assertUnauthorized();
    });

    it('changes the password: the new one works, the old one does not', function (): void {
        $user = student(User::factory()->create(['password' => 'oudwachtwoord1']));

        $this->putJson('/api/v1/auth/password', passwordPayload())->assertNoContent();

        expect(Hash::check('nieuwwachtwoord1', $user->fresh()->password))->toBeTrue();
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'nieuwwachtwoord1'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'oudwachtwoord1'])->assertUnprocessable();
    });

    it('signs out the other devices but not this one', function (): void {
        $user = User::factory()->create(['email' => 'sanne@example.com', 'password' => 'oudwachtwoord1']);
        $login = fn () => $this->postJson('/api/v1/auth/login', ['email' => 'sanne@example.com', 'password' => 'oudwachtwoord1'])->json('data.token');
        $phone = $login();
        $laptop = $login();

        $this->withToken($phone)->putJson('/api/v1/auth/password', passwordPayload())->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->withToken($phone)->getJson('/api/v1/auth/me')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($laptop)->getJson('/api/v1/auth/me')->assertUnauthorized();
        expect($user->tokens()->count())->toBe(1);
    });

    it('rejects a wrong current password, in Dutch, and changes nothing', function (): void {
        $user = student(User::factory()->create(['password' => 'oudwachtwoord1']));

        $this->putJson('/api/v1/auth/password', passwordPayload(['current_password' => 'verkeerd']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.current_password.0', 'Het huidige wachtwoord is onjuist.');

        expect(Hash::check('oudwachtwoord1', $user->fresh()->password))->toBeTrue();
    });

    it('validates the new password', function (array $overrides, string $field, string $message): void {
        student(User::factory()->create(['password' => 'oudwachtwoord1']));

        $this->putJson('/api/v1/auth/password', passwordPayload($overrides))
            ->assertUnprocessable()
            ->assertJsonPath("errors.{$field}.0", $message);
    })->with([
        'too short' => [['password' => 'kort', 'password_confirmation' => 'kort'], 'password', 'Wachtwoord moet minimaal 8 tekens bevatten.'],
        'not confirmed' => [['password_confirmation' => 'anders'], 'password', 'De bevestiging van wachtwoord komt niet overeen.'],
        'same as the current one' => [['password' => 'oudwachtwoord1', 'password_confirmation' => 'oudwachtwoord1'], 'password', 'Wachtwoord moet anders zijn dan huidige wachtwoord.'],
        'no current password' => [['current_password' => ''], 'current_password', 'Huidige wachtwoord is verplicht.'],
        'no new password' => [['password' => '', 'password_confirmation' => ''], 'password', 'Wachtwoord is verplicht.'],
    ]);

    it('limits repeated attempts', function (): void {
        student(User::factory()->create(['password' => 'oudwachtwoord1']));

        for ($i = 0; $i < 5; $i++) {
            $this->putJson('/api/v1/auth/password', passwordPayload(['current_password' => 'fout']))->assertUnprocessable();
        }

        $this->putJson('/api/v1/auth/password', passwordPayload(['current_password' => 'fout']))->assertTooManyRequests();
    });
});
