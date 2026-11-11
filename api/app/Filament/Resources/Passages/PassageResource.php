<?php

declare(strict_types=1);

namespace App\Filament\Resources\Passages;

use App\Filament\Resources\Passages\Pages\CreatePassage;
use App\Filament\Resources\Passages\Pages\EditPassage;
use App\Filament\Resources\Passages\Pages\ListPassages;
use App\Filament\Resources\Passages\Schemas\PassageForm;
use App\Filament\Resources\Passages\Tables\PassagesTable;
use App\Models\Passage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PassageResource extends Resource
{
    protected static ?string $model = Passage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Inhoud';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'tekst';

    protected static ?string $pluralModelLabel = 'teksten';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return PassageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PassagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPassages::route('/'),
            'create' => CreatePassage::route('/create'),
            'edit' => EditPassage::route('/{record}/edit'),
        ];
    }
}
