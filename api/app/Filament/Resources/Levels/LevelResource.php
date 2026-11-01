<?php

declare(strict_types=1);

namespace App\Filament\Resources\Levels;

use App\Filament\Resources\Levels\Pages\ManageLevels;
use App\Filament\Support\SafeDelete;
use App\Models\Level;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

class LevelResource extends Resource
{
    protected static ?string $model = Level::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Inhoud';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'niveau';

    protected static ?string $pluralModelLabel = 'niveaus';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Naam')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                    if (blank($get('slug'))) {
                        $set('slug', Str::slug((string) $state));
                    }
                }),
            TextInput::make('slug')
                ->label('Code')
                ->required()
                ->maxLength(255)
                ->regex('/^[a-z0-9-]+$/')
                ->unique(ignoreRecord: true)
                ->helperText('Kleine letters, cijfers en streepjes. De app gebruikt deze code in filters; wijzig hem alleen als het echt moet.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('name')->label('Naam')->searchable()->weight('medium'),
                        TextColumn::make('slug')->label('Code')->color('gray')->size('sm'),
                    ]),
                    Split::make([
                        TextColumn::make('questions_count')->label('Vragen')->counts('questions')->suffix(' vragen')->color('gray')->size('sm')->grow(false),
                        TextColumn::make('exams_count')->label('Examens')->counts('exams')->suffix(' examens')->color('gray')->size('sm')->grow(false)->visibleFrom('lg'),
                        TextColumn::make('users_count')->label('Leerlingen')->counts('users')->suffix(' leerlingen')->color('gray')->size('sm')->grow(false)->visibleFrom('lg'),
                    ]),
                ])->from('md'),
            ])
            ->recordAction(EditAction::class)
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make()->iconButton(),
                SafeDelete::action(self::deleteBlockedReason(...)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([SafeDelete::bulk(self::deleteBlockedReason(...))]),
            ]);
    }

    public static function deleteBlockedReason(Model $level): ?string
    {
        $questions = $level->questions()->count();
        $exams = $level->exams()->count();

        return $questions + $exams > 0
            ? "Niveau {$level->name} heeft nog {$questions} vragen en {$exams} examens. Verplaats of verwijder die eerst."
            : null;
    }

    public static function getPages(): array
    {
        return ['index' => ManageLevels::route('/')];
    }
}
