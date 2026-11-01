<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Overzicht';

    // No 5 second polling: it would make the busy bar blink all the time and the numbers hardly change.
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $published = Question::published()->count();
        $drafts = Question::count() - $published;

        return [
            Stat::make('Gepubliceerde vragen', $published)->description("{$drafts} concepten"),
            Stat::make('Gepubliceerde examens', Exam::published()->count())->description(Exam::count().' in totaal'),
            Stat::make('Leerlingen', User::where('is_admin', false)->count()),
            Stat::make('Pogingen (7 dagen)', Attempt::where('started_at', '>=', now()->subDays(7))->count()),
        ];
    }
}
