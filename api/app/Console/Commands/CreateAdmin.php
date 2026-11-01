<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;

/**
 * Creates an administrator (or makes an existing account one). The seeded accounts are skipped in production because
 * their passwords are public, so this is how the first admin gets in.
 */
class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin
        {email : E-mail address of the administrator}
        {--name= : Display name (asked for when the account is new)}
        {--password= : Password (asked for when omitted; avoid this option on shared servers, it ends up in the shell history)}';

    protected $description = 'Create an administrator, or promote and update an existing account';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::firstOrNew(['email' => $email]);

        $name = $this->option('name') ?: ($user->exists ? $user->name : $this->ask('Name', 'Beheerder'));
        $plain = $this->option('password')
            ?: ($user->exists && ! $this->confirm('Set a new password?', false) ? null : password('Password (min. 8 characters)'));

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $plain],
            [
                'email' => ['required', 'email'],
                'name' => ['required', 'string', 'max:191'],
                'password' => [$user->exists ? 'nullable' : 'required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $attributes = [
            'name' => $name,
            'is_admin' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ];

        if ($plain !== null) {
            $attributes['password'] = $plain;
        }

        // is_admin is not mass assignable on purpose.
        $user->forceFill($attributes)->save();

        $this->info(($user->wasRecentlyCreated ? 'Created' : 'Updated').' administrator '.$email.'.');

        return self::SUCCESS;
    }
}
