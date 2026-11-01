<?php

declare(strict_types=1);

namespace App\Filament\Resources\Admins\Schemas;

use App\Filament\Support\UserFields;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Beheerder')
                ->description('Beheerders hebben toegang tot dit beheer en kunnen alle vragen, examens en gebruikers beheren.')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    UserFields::name(),
                    UserFields::email(),
                    UserFields::password(),
                ]),
        ]);
    }
}
