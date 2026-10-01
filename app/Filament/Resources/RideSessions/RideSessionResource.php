<?php

namespace App\Filament\Resources\RideSessions;

use App\Filament\Resources\RideSessions\Pages\CreateRideSession;
use App\Filament\Resources\RideSessions\Pages\EditRideSession;
use App\Filament\Resources\RideSessions\Pages\ListRideSessions;
use App\Filament\Resources\RideSessions\Schemas\RideSessionForm;
use App\Filament\Resources\RideSessions\Tables\RideSessionsTable;
use App\Models\RideSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RideSessionResource extends Resource
{
    protected static ?string $model = RideSession::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    public static function getNavigationBadge(): ?string
    {
        $activeCount = static::getModel()::where('status', 'active')->count();
        return $activeCount > 0 ? (string) $activeCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return RideSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RideSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRideSessions::route('/'),
            'create' => CreateRideSession::route('/create'),
            'edit'   => EditRideSession::route('/{record}/edit'),
        ];
    }
}
