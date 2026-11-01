<?php

declare(strict_types=1);

namespace App\Filament\Resources\Exams\Schemas;

use App\Models\Question;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Examen')
                ->columns(2)
                ->schema([
                    TextInput::make('title')->label('Titel')->required()->maxLength(191)->columnSpanFull(),
                    Select::make('level_id')
                        ->label('Niveau')
                        ->relationship('level', 'name')
                        ->required()
                        ->preload()
                        ->live()
                        ->helperText('Bij het kiezen van vragen zie je de vragen van dit niveau.'),
                    Toggle::make('is_published')
                        ->label('Gepubliceerd')
                        ->live()
                        ->helperText('Alleen gepubliceerde examens zijn zichtbaar voor leerlingen. Een gepubliceerd examen heeft minstens één vraag nodig.')
                        ->inline(false),
                    TextInput::make('time_limit_minutes')
                        ->label('Tijdslimiet')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(300)
                        ->default(30)
                        ->suffix('minuten'),
                    TextInput::make('pass_mark_percent')
                        ->label('Slagingspercentage')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->default(55)
                        ->suffix('%'),
                ]),

            Section::make('Vragen')
                ->description('Alleen gepubliceerde vragen kunnen worden toegevoegd. Sleep om de volgorde te wijzigen.')
                ->schema([
                    Repeater::make('examQuestions')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('sort_order')
                        ->reorderableWithDragAndDrop()
                        ->schema([
                            Select::make('question_id')
                                ->label('Vraag')
                                ->required()
                                ->searchable()
                                ->options(fn (Get $get): array => self::questionOptions($get('../../level_id')))
                                ->distinct()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->columnSpan(4),
                            TextInput::make('points')
                                ->label('Punten')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(100)
                                ->default(1)
                                ->columnSpan(1),
                        ])
                        ->columns(5)
                        ->defaultItems(0)
                        ->minItems(fn (Get $get): int => $get('is_published') ? 1 : 0)
                        ->addActionLabel('Vraag toevoegen')
                        ->itemLabel(fn (array $state): ?string => filled($state['question_id'] ?? null)
                            ? Str::limit((string) Question::find($state['question_id'])?->summary(), 70)
                            : null),
                ]),
        ]);
    }

    /** @return array<int, string> */
    private static function questionOptions(mixed $levelId): array
    {
        return Question::published()
            ->with('topic')
            ->when($levelId, fn ($q, $level) => $q->where('level_id', $level))
            ->orderBy('topic_id')
            ->orderBy('id')
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (Question $q) => [
                $q->id => "[{$q->type->getLabel()} · {$q->topic->name}] ".Str::limit($q->summary(), 90),
            ])
            ->all();
    }
}
