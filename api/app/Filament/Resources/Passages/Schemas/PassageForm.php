<?php

declare(strict_types=1);

namespace App\Filament\Resources\Passages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PassageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('title')
                ->label('Titel')
                ->required()
                ->maxLength(191)
                ->helperText('De leerling ziet de titel boven de tekst.'),
            Textarea::make('body')
                ->label('Tekst')
                ->required()
                ->rows(16)
                ->helperText('Gewone tekst zonder opmaak. Een lege regel begint een nieuwe alinea; een enter binnen een alinea blijft een regeleinde. De vragen bij deze tekst maak je bij Vragen: kies daar bij "Bij tekst" deze tekst.'),
        ]);
    }
}
