<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Closure;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Delete actions that refuse, with an explanation, when a record is still in use.
 * The database also restricts these deletes; this just gives a friendly message instead of an SQL error.
 */
final class SafeDelete
{
    /** @param  Closure(Model): ?string  $reason  Returns why the record cannot be deleted, or null. */
    public static function action(Closure $reason, ?string $description = null): DeleteAction
    {
        return DeleteAction::make()
            ->modalDescription($description)
            ->before(function (DeleteAction $action, Model $record) use ($reason): void {
                if ($message = $reason($record)) {
                    self::blocked($message);
                    $action->cancel();
                }
            });
    }

    /** @param  Closure(Model): ?string  $reason */
    public static function bulk(Closure $reason): BulkAction
    {
        return BulkAction::make('delete')
            ->label('Verwijderen')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) use ($reason): void {
                $deleted = 0;
                $skipped = [];

                foreach ($records as $record) {
                    if ($message = $reason($record)) {
                        $skipped[] = $message;

                        continue;
                    }
                    $record->delete();
                    $deleted++;
                }

                if ($deleted > 0) {
                    Notification::make()->success()->title("{$deleted} verwijderd")->send();
                }
                if ($skipped !== []) {
                    self::blocked(implode("\n", array_unique($skipped)));
                }
            });
    }

    public static function blocked(string $message): void
    {
        Notification::make()->warning()->title('Kan niet worden verwijderd')->body($message)->persistent()->send();
    }
}
