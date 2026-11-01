<?php

declare(strict_types=1);

namespace App\Filament\Resources\Questions\Schemas;

use App\Enums\QuestionType;
use App\Support\QuestionFormMapper;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Algemeen')
                ->columns(2)
                ->schema([
                    Select::make('topic_id')
                        ->label('Onderwerp')
                        ->relationship('topic', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('level_id')
                        ->label('Niveau')
                        ->relationship('level', 'name')
                        ->required()
                        ->preload(),
                    Select::make('type')
                        ->label('Soort vraag')
                        ->options(QuestionType::class)
                        ->required()
                        ->live()
                        ->helperText('Bepaalt welke velden je hieronder invult.'),
                    Select::make('difficulty')
                        ->label('Moeilijkheid')
                        ->options([1 => '1 - heel makkelijk', 2 => '2 - makkelijk', 3 => '3 - gemiddeld', 4 => '4 - moeilijk', 5 => '5 - heel moeilijk'])
                        ->required()
                        ->default(1),
                    Textarea::make('prompt')
                        ->label('Opdracht')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull()
                        ->helperText('De vraag of opdracht zoals de leerling die leest.'),
                    Toggle::make('is_published')
                        ->label('Gepubliceerd')
                        ->helperText('Alleen gepubliceerde vragen zijn zichtbaar voor leerlingen.'),
                ]),

            Section::make('Inhoud van de vraag')
                ->visible(fn (Get $get) => filled($get('type')))
                ->schema([
                    self::multipleChoice(),
                    self::fillBlank(),
                    self::orderWords(),
                    self::markError(),
                    self::parseSentence(),
                ]),

            Section::make('Uitleg')
                ->schema([
                    Textarea::make('explanation')
                        ->label('Uitleg bij het antwoord')
                        ->required()
                        ->rows(6)
                        ->helperText('Leerlingen zien dit na het antwoord. Schrijf helder Nederlands op het niveau van de leerling en leg uit waarom het antwoord klopt.'),
                ]),
        ]);
    }

    private static function typeIs(Get $get, QuestionType $type): bool
    {
        $value = $get('type');

        return ($value instanceof QuestionType ? $value->value : $value) === $type->value;
    }

    private static function multipleChoice(): Group
    {
        return Group::make()
            ->visible(fn (Get $get) => self::typeIs($get, QuestionType::MultipleChoice))
            ->schema([
                Repeater::make('editor.multiple_choice.options')
                    ->label('Antwoordopties')
                    ->helperText('Zet "Juist" aan bij elk goed antwoord. Meer dan één juist antwoord maakt er een vraag met meerdere antwoorden van.')
                    ->schema([
                        TextInput::make('text')->label('Antwoord')->required()->columnSpan(3),
                        Toggle::make('correct')->label('Juist')->inline(false),
                    ])
                    ->columns(4)
                    ->minItems(2)
                    ->maxItems(8)
                    ->defaultItems(2)
                    ->reorderable()
                    ->addActionLabel('Optie toevoegen')
                    ->itemLabel(fn (array $state): ?string => $state['text'] ?? null)
                    ->rules([
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (! collect($value)->contains(fn ($o) => ! empty($o['correct']))) {
                                $fail('Kies minstens één juist antwoord.');
                            }
                        },
                    ]),
            ]);
    }

    private static function fillBlank(): Group
    {
        return Group::make()
            ->visible(fn (Get $get) => self::typeIs($get, QuestionType::FillBlank))
            ->schema([
                Textarea::make('editor.fill_blank.text')
                    ->label('Tekst met invulplekken')
                    ->required()
                    ->rows(3)
                    ->helperText('Zet {{blank}} op de plek van elk invulveld. Bijvoorbeeld: Mijn broer {{blank}} in een ziekenhuis.'),
                Repeater::make('editor.fill_blank.blanks')
                    ->label('Invulvelden (in de volgorde van de tekst)')
                    ->schema([
                        TagsInput::make('accepted')
                            ->label('Goede antwoorden')
                            ->required()
                            ->placeholder('Typ een antwoord en druk op Enter')
                            ->helperText('Zijn er meer goede schrijfwijzen? Voeg ze allemaal toe.'),
                        TextInput::make('hint')
                            ->label('Hint (optioneel)')
                            ->helperText('Bijvoorbeeld de hele werkwoordsvorm tussen haakjes.'),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->defaultItems(1)
                    ->addActionLabel('Invulveld toevoegen'),
                Toggle::make('editor.fill_blank.case_sensitive')
                    ->label('Hoofdletters tellen mee')
                    ->helperText('Uit: "amsterdam" is ook goed. Aan: alleen "Amsterdam".'),
                Toggle::make('editor.fill_blank.ignore_extra_whitespace')
                    ->label('Extra spaties negeren')
                    ->default(true),
            ]);
    }

    private static function orderWords(): Group
    {
        return Group::make()
            ->visible(fn (Get $get) => self::typeIs($get, QuestionType::OrderWords))
            ->schema([
                TextInput::make('editor.order_words.sentence')
                    ->label('Goede zin')
                    ->required()
                    ->helperText('Typ de zin in de goede volgorde. Elk woord (gescheiden door een spatie) wordt een blokje; de leerling krijgt ze door elkaar.'),
                Textarea::make('editor.order_words.alternatives')
                    ->label('Andere goede volgordes (optioneel)')
                    ->rows(3)
                    ->helperText('Een zin per regel, met precies dezelfde woorden. Bijvoorbeeld: Ik ging gisteren zwemmen'),
                Toggle::make('editor.order_words.case_sensitive')
                    ->label('Hoofdletters tellen mee')
                    ->helperText('Meestal uit, zodat een zin met een ander eerste woord ook goed is.'),
            ]);
    }

    private static function markError(): Group
    {
        return Group::make()
            ->visible(fn (Get $get) => self::typeIs($get, QuestionType::MarkError))
            ->schema([
                TextInput::make('editor.mark_error.sentence')
                    ->label('Zin met één fout woord')
                    ->required()
                    ->helperText('Zet sterretjes om het foute woord. Bijvoorbeeld: Wij *fietst* naar school.'),
                TextInput::make('editor.mark_error.correction')
                    ->label('Juiste vorm van het foute woord')
                    ->required()
                    ->helperText('Bijvoorbeeld: fietsen. Leerlingen zien dit na het antwoord.'),
            ]);
    }

    private static function parseSentence(): Group
    {
        return Group::make()
            ->visible(fn (Get $get) => self::typeIs($get, QuestionType::ParseSentence))
            ->schema([
                TagsInput::make('editor.parse_sentence.roles')
                    ->label('Functies waaruit de leerling kiest')
                    ->suggestions(QuestionFormMapper::SUGGESTED_ROLES)
                    ->required()
                    ->live(onBlur: true)
                    ->helperText('Kies uit de suggesties of typ een eigen functie. Voor lagere niveaus bied je minder functies aan.'),
                Repeater::make('editor.parse_sentence.tokens')
                    ->label('Zinsdelen (in de volgorde van de zin)')
                    ->schema([
                        TextInput::make('text')->label('Zinsdeel')->required(),
                        Select::make('role')
                            ->label('Juiste functie')
                            ->required()
                            ->options(fn (Get $get): array => collect($get('../../roles') ?? [])
                                ->mapWithKeys(fn ($role) => [$role => $role])
                                ->all()),
                    ])
                    ->columns(2)
                    ->minItems(2)
                    ->defaultItems(2)
                    ->reorderable()
                    ->addActionLabel('Zinsdeel toevoegen')
                    ->itemLabel(fn (array $state): ?string => $state['text'] ?? null),
            ]);
    }
}
