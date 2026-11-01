<?php

declare(strict_types=1);

namespace App\Filament\Resources\Admins;

use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Admins\Pages\EditAdmin;
use App\Filament\Resources\Admins\Pages\ListAdmins;
use App\Filament\Resources\Admins\Schemas\AdminForm;
use App\Filament\Resources\Admins\Tables\AdminsTable;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AdminResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'admins';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Gebruikers';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'beheerder';

    protected static ?string $pluralModelLabel = 'beheerders';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_admin', true);
    }

    public static function form(Schema $schema): Schema
    {
        return AdminForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminsTable::configure($table);
    }

    /**
     * Why this administrator cannot be removed or turned into a student, or null when that is fine.
     * An admin can never remove themselves, so at least one administrator always remains.
     */
    public static function removalBlockedReason(Model $admin): ?string
    {
        if ($admin->is(Filament::auth()->user())) {
            return 'Je kunt je eigen beheerdersaccount niet verwijderen of omzetten. Vraag een andere beheerder om dat te doen.';
        }

        if (User::where('is_admin', true)->count() <= 1) {
            return 'Er moet minstens één beheerder overblijven.';
        }

        return null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
            'edit' => EditAdmin::route('/{record}/edit'),
        ];
    }
}
