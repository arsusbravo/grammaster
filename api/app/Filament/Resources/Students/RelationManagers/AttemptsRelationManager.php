<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\RelationManagers;

use App\Enums\AttemptMode;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** The student's practice sessions and exams (read-only). */
class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    protected static ?string $title = 'Oefenen en examens';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('exam')
                ->withCount(['answers', 'answers as correct_count' => fn (Builder $q) => $q->where('is_correct', true)]))
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('started_at')->label('Gestart')->dateTime('d-m-Y H:i')->sortable(),
                TextColumn::make('mode')->label('Soort')->badge(),
                TextColumn::make('exam.title')->label('Examen')->placeholder('-')->visibleFrom('md'),
                TextColumn::make('score')->label('Score')->placeholder('bezig')->formatStateUsing(fn ($state) => round((float) $state).'%'),
                TextColumn::make('correct_count')->label('Goed')->formatStateUsing(fn ($state, $record) => "{$state} van {$record->answers_count}")->visibleFrom('sm'),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('mode')->label('Soort')->options(AttemptMode::class),
            ])
            ->paginated([10, 25]);
    }
}
