<?php

declare(strict_types=1);

namespace App\Filament\Resources\Passages\Pages;

use App\Filament\Resources\Passages\PassageResource;
use App\Filament\Resources\Passages\Tables\PassagesTable;
use Filament\Resources\Pages\EditRecord;

class EditPassage extends EditRecord
{
    protected static string $resource = PassageResource::class;

    protected function getHeaderActions(): array
    {
        return [PassagesTable::deleteAction()];
    }
}
