<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $student = new User($data);
        // is_admin is deliberately not mass assignable.
        $student->forceFill(['is_admin' => false, 'email_verified_at' => now()])->save();

        return $student;
    }
}
