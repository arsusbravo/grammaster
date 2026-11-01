<?php

declare(strict_types=1);

namespace App\Filament\Resources\Topics\Pages;

use App\Filament\Resources\Topics\TopicResource;
use App\Models\Topic;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTopics extends ManageRecords
{
    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(fn (array $data) => $data + ['sort_order' => (int) Topic::max('sort_order') + 1]),
        ];
    }
}
