<?php

declare(strict_types=1);

namespace App\Filament\Resources\Admins\Tables;

use App\Filament\Resources\Admins\AdminResource;
use App\Filament\Support\SafeDelete;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdminsTable
{
    public static function configure(Table $table): Table
    {
        $you = fn (User $record): ?string => $record->is(Filament::auth()->user()) ? 'Jij' : null;

        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('name')->label('Naam')->searchable()->sortable()->weight('medium'),
                        TextColumn::make('email')->label('E-mailadres')->searchable()->copyable()->color('gray')->size('sm'),
                    ]),
                    TextColumn::make('you')->label('')->state($you)->badge()->color('primary')->grow(false),
                ])->from('md'),
            ])
            ->defaultSort('name')
            ->recordUrl(fn (User $record) => route('filament.admin.resources.admins.edit', $record))
            ->recordActions([
                EditAction::make()->iconButton(),
                ActionGroup::make([
                    Action::make('makeStudent')
                        ->label('Maak leerling')
                        ->icon(Heroicon::OutlinedUser)
                        ->requiresConfirmation()
                        ->modalDescription('Deze persoon heeft geen toegang meer tot dit beheer, maar kan nog wel oefenen in de app.')
                        ->action(function (User $record): void {
                            if ($reason = AdminResource::removalBlockedReason($record)) {
                                Notification::make()->warning()->title('Dat kan niet')->body($reason)->persistent()->send();

                                return;
                            }

                            $record->forceFill(['is_admin' => false])->save();
                            Notification::make()->success()->title("{$record->name} is nu een leerling")->send();
                        }),
                    SafeDelete::action(AdminResource::removalBlockedReason(...)),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([SafeDelete::bulk(AdminResource::removalBlockedReason(...))]),
            ]);
    }
}
