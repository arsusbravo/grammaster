<?php

declare(strict_types=1);

namespace App\Filament\Resources\Passages\Tables;

use App\Filament\Support\SafeDelete;
use App\Models\Passage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PassagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('title')->label('Titel')->searchable()->weight('medium'),
                        TextColumn::make('body')->label('Tekst')->state(fn (Passage $record) => Str::limit(preg_replace('/\s+/u', ' ', $record->body) ?? '', 110))->color('gray')->size('sm')->wrap(),
                    ]),
                    TextColumn::make('questions_count')->label('Vragen')->counts('questions')->suffix(' vragen')->color('gray')->size('sm')->grow(false),
                ])->from('md'),
            ])
            ->defaultSort('id', 'desc')
            ->recordUrl(fn (Passage $record) => route('filament.admin.resources.passages.edit', $record))
            ->recordActions([
                EditAction::make()->iconButton(),
                self::deleteAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([SafeDelete::bulk(self::deleteBlockedReason(...))]),
            ]);
    }

    public static function deleteAction(): DeleteAction
    {
        return SafeDelete::action(self::deleteBlockedReason(...));
    }

    public static function deleteBlockedReason(Model $passage): ?string
    {
        $questions = $passage->questions()->count();

        return $questions > 0
            ? "De tekst {$passage->title} heeft nog {$questions} vragen. Verwijder die eerst of koppel ze aan een andere tekst."
            : null;
    }
}
