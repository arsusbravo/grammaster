<?php

declare(strict_types=1);

namespace App\Filament\Resources\Passages\Pages;

use App\Filament\Resources\Passages\PassageResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePassage extends CreateRecord
{
    protected static string $resource = PassageResource::class;
}
