<?php

namespace App\Filament\Resources\PromotionBanners\Pages;

use App\Filament\Resources\PromotionBanners\PromotionBannerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPromotionBanner extends EditRecord
{
    protected static string $resource = PromotionBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
