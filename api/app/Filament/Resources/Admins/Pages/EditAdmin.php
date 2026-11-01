<?php

declare(strict_types=1);

namespace App\Filament\Resources\Admins\Pages;

use App\Filament\Resources\Admins\AdminResource;
use App\Filament\Support\SafeDelete;
use App\Filament\Support\SignsOutOnPasswordChange;
use Filament\Resources\Pages\EditRecord;

class EditAdmin extends EditRecord
{
    use SignsOutOnPasswordChange;

    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [SafeDelete::action(AdminResource::removalBlockedReason(...))];
    }
}
