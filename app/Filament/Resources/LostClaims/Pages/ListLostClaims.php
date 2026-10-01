<?php

namespace App\Filament\Resources\LostClaims\Pages;

use App\Filament\Resources\LostClaims\LostClaimResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLostClaims extends ListRecords
{
    protected static string $resource = LostClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
