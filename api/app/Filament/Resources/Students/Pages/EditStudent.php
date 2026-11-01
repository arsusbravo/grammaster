<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Filament\Support\SafeDelete;
use App\Filament\Support\SignsOutOnPasswordChange;
use Filament\Resources\Pages\EditRecord;

class EditStudent extends EditRecord
{
    use SignsOutOnPasswordChange;

    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SafeDelete::action(
                fn () => null,
                'Het account en alle oefenresultaten van deze leerling worden definitief verwijderd.',
            ),
        ];
    }
}
