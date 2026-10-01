<?php

namespace App\Filament\Resources\PromotionBanners\Pages;

use App\Filament\Resources\PromotionBanners\PromotionBannerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPromotionBanners extends ListRecords
{
    protected static string $resource = PromotionBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
