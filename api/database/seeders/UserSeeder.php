<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Level;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Local development accounts. Skipped in production (these passwords are public). */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Skipping default users in production.');

            return;
        }

        $this->upsert('Admin', 'info@arsus.nl', 'admin1234', isAdmin: true);
        $this->upsert('Test Leerling', 'test@arsus.nl', 'test1234', levelId: Level::where('slug', 'havo')->value('id'));
    }

    private function upsert(string $name, string $email, string $password, bool $isAdmin = false, ?int $levelId = null): void
    {
        $user = User::firstOrNew(['email' => $email]);

        // is_admin is not mass assignable on purpose, so set it explicitly.
        $user->forceFill([
            'name' => $name,
            'password' => $password,
            'level_id' => $levelId,
            'is_admin' => $isAdmin,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
    }
}
