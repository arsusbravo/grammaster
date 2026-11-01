<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

/** Form fields shared by the student and administrator resources. */
final class UserFields
{
    public static function name(): TextInput
    {
        return TextInput::make('name')->label('Naam')->required()->maxLength(255);
    }

    public static function email(): TextInput
    {
        return TextInput::make('email')
            ->label('E-mailadres')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(ignoreRecord: true);
    }

    /** Required when creating; when editing, empty means "keep the current password". */
    public static function password(): TextInput
    {
        return TextInput::make('password')
            ->label(fn (string $operation) => $operation === 'create' ? 'Wachtwoord' : 'Nieuw wachtwoord')
            ->password()
            ->revealable()
            ->autocomplete('new-password')
            ->minLength(8)
            ->maxLength(255)
            ->required(fn (string $operation) => $operation === 'create')
            ->dehydrated(fn (?string $state) => filled($state))
            ->helperText(fn (string $operation) => $operation === 'create'
                ? 'Minimaal 8 tekens.'
                : 'Laat leeg om het wachtwoord niet te wijzigen.');
    }
}
