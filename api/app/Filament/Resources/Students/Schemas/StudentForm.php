<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Schemas;

use App\Filament\Support\UserFields;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Leerling')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    UserFields::name(),
                    UserFields::email(),
                    Select::make('level_id')
                        ->label('Niveau')
                        ->relationship('level', 'name')
                        ->placeholder('Nog niet gekozen')
                        ->preload(),
                    UserFields::password(),
                ]),
        ]);
    }
}
