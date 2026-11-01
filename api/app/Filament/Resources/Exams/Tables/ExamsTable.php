<?php

declare(strict_types=1);

namespace App\Filament\Resources\Exams\Tables;

use App\Filament\Support\SafeDelete;
use App\Models\Exam;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('questions')->withSum('questions', 'exam_questions.points'))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('title')->label('Titel')->searchable()->sortable()->weight('medium'),
                        TextColumn::make('level.name')->label('Niveau')->badge()->color('gray')->sortable(),
                    ]),
                    Split::make([
                        TextColumn::make('questions_count')->label('Vragen')->suffix(' vragen')->color('gray')->size('sm')->grow(false),
                        TextColumn::make('questions_sum_exam_questionspoints')->label('Punten')->default(0)->suffix(' punten')->color('gray')->size('sm')->grow(false),
                        TextColumn::make('time_limit_minutes')->label('Tijd')->suffix(' min')->color('gray')->size('sm')->grow(false)->visibleFrom('lg'),
                        TextColumn::make('pass_mark_percent')->label('Norm')->prefix('norm ')->suffix('%')->color('gray')->size('sm')->grow(false)->visibleFrom('lg'),
                        IconColumn::make('is_published')->label('Gepubliceerd')->boolean()->grow(false),
                    ]),
                ])->from('md'),
            ])
            ->defaultSort('id', 'desc')
            ->recordUrl(fn (Exam $record) => route('filament.admin.resources.exams.edit', $record))
            ->filters([
                SelectFilter::make('level_id')->label('Niveau')->relationship('level', 'name'),
                TernaryFilter::make('is_published')->label('Gepubliceerd')->trueLabel('Gepubliceerd')->falseLabel('Concept'),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                ActionGroup::make([
                    Action::make('togglePublished')
                        ->label(fn (Exam $record) => $record->is_published ? 'Naar concept' : 'Publiceren')
                        ->icon(fn (Exam $record) => $record->is_published ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedEye)
                        ->action(function (Exam $record): void {
                            if (! $record->is_published && ! $record->questions()->exists()) {
                                Notification::make()->warning()->title('Voeg eerst vragen toe')
                                    ->body('Een examen zonder vragen kan niet worden gepubliceerd.')->send();

                                return;
                            }

                            $record->update(['is_published' => ! $record->is_published]);
                        }),
                    SafeDelete::action(
                        fn () => null,
                        'De resultaten van leerlingen blijven bewaard, maar zijn daarna niet meer aan dit examen gekoppeld.',
                    ),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([SafeDelete::bulk(fn () => null)]),
            ]);
    }
}
