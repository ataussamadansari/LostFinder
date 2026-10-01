<?php

namespace App\Filament\Resources\LostClaims;

use App\Filament\Resources\LostClaims\Pages\CreateLostClaim;
use App\Filament\Resources\LostClaims\Pages\EditLostClaim;
use App\Filament\Resources\LostClaims\Pages\ListLostClaims;
use App\Filament\Resources\LostClaims\Schemas\LostClaimForm;
use App\Filament\Resources\LostClaims\Tables\LostClaimsTable;
use App\Models\LostClaim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LostClaimResource extends Resource
{
    protected static ?string $model = LostClaim::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Claims & Disputes';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    public static function getNavigationBadge(): ?string
    {
        $openCount = static::getModel()::whereIn('claim_status', ['reported', 'searching', 'found', 'disputed'])->count();
        return $openCount > 0 ? (string) $openCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return LostClaimForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LostClaimsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLostClaims::route('/'),
            'create' => CreateLostClaim::route('/create'),
            'edit'   => EditLostClaim::route('/{record}/edit'),
        ];
    }
}
