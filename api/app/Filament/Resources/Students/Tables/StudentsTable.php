<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Tables;

use App\Filament\Support\SafeDelete;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('attempts')->withMax('attempts', 'started_at'))
            ->columns([
                // One row per student: stacked on a phone, side by side from the md breakpoint.
                Split::make([
                    Stack::make([
                        TextColumn::make('name')->label('Naam')->searchable()->sortable()->weight('medium'),
                        TextColumn::make('email')->label('E-mailadres')->searchable()->copyable()->color('gray')->size('sm'),
                    ]),
                    Split::make([
                        TextColumn::make('level.name')->label('Niveau')->badge()->placeholder('Geen niveau')->sortable()->grow(false),
                        TextColumn::make('attempts_count')
                            ->label('Pogingen')
                            ->sortable()
                            ->formatStateUsing(fn ($state) => $state === 1 ? '1 poging' : "{$state} pogingen")
                            ->color('gray')
                            ->size('sm')
                            ->grow(false),
                        TextColumn::make('attempts_max_started_at')
                            ->label('Laatst actief')
                            ->since()
                            ->placeholder('Nog niet actief')
                            ->sortable()
                            ->color('gray')
                            ->size('sm')
                            ->grow(false)
                            ->visibleFrom('lg'),
                    ]),
                ])->from('md'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('level_id')->label('Niveau')->relationship('level', 'name'),
                TernaryFilter::make('active')
                    ->label('Heeft geoefend')
                    ->queries(
                        true: fn (Builder $q) => $q->has('attempts'),
                        false: fn (Builder $q) => $q->doesntHave('attempts'),
                        blank: fn (Builder $q) => $q,
                    ),
            ])
            ->recordUrl(fn (User $record) => route('filament.admin.resources.students.edit', $record))
            ->recordActions([
                EditAction::make()->iconButton(),
                ActionGroup::make([
                    Action::make('makeAdmin')
                        ->label('Maak beheerder')
                        ->icon(Heroicon::OutlinedShieldCheck)
                        ->requiresConfirmation()
                        ->modalDescription('Deze persoon krijgt toegang tot dit beheer en kan alle vragen, examens en gebruikers beheren. Zijn of haar oefengeschiedenis blijft bewaard.')
                        ->action(function (User $record): void {
                            $record->forceFill(['is_admin' => true])->save();
                            $record->tokens()->delete();
                            Notification::make()->success()->title("{$record->name} is nu beheerder")->send();
                        }),
                    SafeDelete::action(
                        fn () => null,
                        'Het account en alle oefenresultaten van deze leerling worden definitief verwijderd.',
                    ),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([SafeDelete::bulk(fn () => null)]),
            ]);
    }
}
