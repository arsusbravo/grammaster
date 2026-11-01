<?php

declare(strict_types=1);

namespace App\Filament\Resources\Admins\Pages;

use App\Filament\Resources\Admins\AdminResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $admin = new User($data);
        // is_admin is deliberately not mass assignable.
        $admin->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();

        return $admin;
    }
}
