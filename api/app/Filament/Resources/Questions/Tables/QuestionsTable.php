<?php

declare(strict_types=1);

namespace App\Filament\Resources\Questions\Tables;

use App\Enums\QuestionType;
use App\Filament\Support\SafeDelete;
use App\Models\Question;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('summary')
                            ->label('Inhoud')
                            ->state(fn (Question $record) => $record->summary())
                            ->limit(90)
                            ->wrap()
                            ->weight('medium')
                            ->description(fn (Question $record) => Str::limit($record->prompt, 70))
                            // Search the prompt and the question content (the data JSON).
                            ->searchable(query: fn (Builder $query, string $search) => $query->where(
                                fn (Builder $q) => $q->where('prompt', 'like', "%{$search}%")->orWhere('data', 'like', "%{$search}%"),
                            )),
                    ]),
                    Split::make([
                        TextColumn::make('type')->label('Soort')->badge()->grow(false),
                        TextColumn::make('level.name')->label('Niveau')->badge()->color('gray')->sortable()->grow(false),
                        TextColumn::make('topic.name')->label('Onderwerp')->sortable()->color('gray')->size('sm')->grow(false)->visibleFrom('lg'),
                        TextColumn::make('difficulty')->label('Moeilijkheid')->sortable()->prefix('Niveau ')->color('gray')->size('sm')->grow(false)->visibleFrom('xl'),
                        IconColumn::make('is_published')->label('Gepubliceerd')->boolean()->sortable()->grow(false),
                    ]),
                ])->from('md'),
            ])
            ->defaultSort('id', 'desc')
            ->recordUrl(fn (Question $record) => route('filament.admin.resources.questions.edit', $record))
            ->filters([
                SelectFilter::make('type')->label('Soort')->options(QuestionType::class),
                SelectFilter::make('topic_id')->label('Onderwerp')->relationship('topic', 'name'),
                SelectFilter::make('level_id')->label('Niveau')->relationship('level', 'name'),
                SelectFilter::make('difficulty')->label('Moeilijkheid')->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                TernaryFilter::make('is_published')->label('Gepubliceerd')->trueLabel('Gepubliceerd')->falseLabel('Concept'),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                ActionGroup::make([
                    self::togglePublishedAction(),
                    ReplicateAction::make()
                        ->label('Dupliceren')
                        // A copy starts as a draft so it never goes live by accident.
                        ->mutateRecordDataUsing(fn (array $data) => [...$data, 'is_published' => false])
                        ->successRedirectUrl(fn (Model $replica) => route('filament.admin.resources.questions.edit', $replica)),
                    self::deleteAction(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Publiceren')
                        ->icon(Heroicon::OutlinedEye)
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')
                        ->label('Terug naar concept')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->action(function (Collection $records): void {
                            $blocked = $records->filter(fn (Question $q) => $q->publishedExamTitles()->isNotEmpty());
                            $records->diff($blocked)->each->update(['is_published' => false]);

                            if ($blocked->isNotEmpty()) {
                                self::unpublishBlocked($blocked->count());
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    SafeDelete::bulk(self::deleteBlockedReason(...)),
                ]),
            ]);
    }

    public static function deleteAction(): DeleteAction
    {
        return SafeDelete::action(self::deleteBlockedReason(...));
    }

    private static function togglePublishedAction(): Action
    {
        return Action::make('togglePublished')
            ->label(fn (Question $record) => $record->is_published ? 'Naar concept' : 'Publiceren')
            ->icon(fn (Question $record) => $record->is_published ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedEye)
            ->action(function (Question $record): void {
                if ($record->is_published && $record->publishedExamTitles()->isNotEmpty()) {
                    self::unpublishBlocked(1, $record->publishedExamTitles()->all());

                    return;
                }

                $record->update(['is_published' => ! $record->is_published]);
            });
    }

    public static function deleteBlockedReason(Model $question): ?string
    {
        /** @var Question $question */
        return $question->isInUse()
            ? 'Deze vraag is beantwoord door leerlingen of zit in een examen. Zet hem terug naar concept in plaats van te verwijderen.'
            : null;
    }

    /** @param  list<string>  $exams */
    public static function unpublishBlocked(int $count, array $exams = []): void
    {
        Notification::make()
            ->warning()
            ->title($count === 1 ? 'Deze vraag blijft gepubliceerd' : "{$count} vragen blijven gepubliceerd")
            ->body('Ze zitten in een gepubliceerd examen'.($exams ? ': '.implode(', ', $exams) : '').'. Haal ze eerst uit het examen of zet het examen op concept.')
            ->persistent()
            ->send();
    }
}
