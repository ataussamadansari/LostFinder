<?php

namespace App\Filament\Resources\PromotionBanners;

use App\Filament\Resources\PromotionBanners\Pages\CreatePromotionBanner;
use App\Filament\Resources\PromotionBanners\Pages\EditPromotionBanner;
use App\Filament\Resources\PromotionBanners\Pages\ListPromotionBanners;
use App\Filament\Resources\PromotionBanners\Schemas\PromotionBannerForm;
use App\Filament\Resources\PromotionBanners\Tables\PromotionBannersTable;
use App\Models\PromotionBanner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PromotionBannerResource extends Resource
{
    protected static ?string $model = PromotionBanner::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Marketing & Ads';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    public static function getNavigationBadge(): ?string
    {
        $activeCount = static::getModel()::where('is_active', true)->count();
        return $activeCount > 0 ? (string) $activeCount : null;
    }

    public static function form(Schema $schema): Schema
    {
        return PromotionBannerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PromotionBannersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPromotionBanners::route('/'),
            'create' => CreatePromotionBanner::route('/create'),
            'edit'   => EditPromotionBanner::route('/{record}/edit'),
        ];
    }
}
