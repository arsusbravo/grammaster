<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Facades\Filament;

/**
 * For the edit pages of user resources. After a new password is saved:
 *  - the user's API tokens are revoked, so a student is signed out everywhere;
 *  - when an admin changed their OWN password, the current session is kept valid
 *    (Filament's AuthenticateSession would otherwise sign them out at once).
 */
trait SignsOutOnPasswordChange
{
    protected bool $passwordChanged = false;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->passwordChanged = filled($data['password'] ?? null);

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->passwordChanged) {
            return;
        }

        $this->record->tokens()->delete();

        if (request()->hasSession() && $this->record->is(Filament::auth()->user())) {
            request()->session()->put('password_hash_'.Filament::getAuthGuard(), $this->record->getAuthPassword());
        }
    }
}
