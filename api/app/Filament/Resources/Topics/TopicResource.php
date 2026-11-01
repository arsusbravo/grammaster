<?php

declare(strict_types=1);

namespace App\Filament\Resources\Topics;

use App\Filament\Resources\Topics\Pages\ManageTopics;
use App\Filament\Support\SafeDelete;
use App\Models\Topic;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
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

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Inhoud';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'onderwerp';

    protected static ?string $pluralModelLabel = 'onderwerpen';

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
            Textarea::make('description')
                ->label('Beschrijving')
                ->rows(3)
                ->columnSpanFull()
                ->helperText('Korte uitleg die leerlingen bij het onderwerp zien.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('name')->label('Naam')->searchable()->weight('medium'),
                        TextColumn::make('description')->label('Beschrijving')->limit(80)->color('gray')->size('sm'),
                    ]),
                    TextColumn::make('questions_count')->label('Vragen')->counts('questions')->suffix(' vragen')->color('gray')->size('sm')->grow(false),
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

    public static function deleteBlockedReason(Model $topic): ?string
    {
        $questions = $topic->questions()->count();

        return $questions > 0
            ? "Onderwerp {$topic->name} heeft nog {$questions} vragen. Verplaats of verwijder die eerst."
            : null;
    }

    public static function getPages(): array
    {
        return ['index' => ManageTopics::route('/')];
    }
}
