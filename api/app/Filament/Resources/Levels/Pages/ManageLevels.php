<?php

declare(strict_types=1);

namespace App\Filament\Resources\Levels\Pages;

use App\Filament\Resources\Levels\LevelResource;
use App\Models\Level;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageLevels extends ManageRecords
{
    protected static string $resource = LevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                // New levels go to the end of the list.
                ->mutateDataUsing(fn (array $data) => $data + ['sort_order' => (int) Level::max('sort_order') + 1]),
        ];
    }
}
