<?php

namespace App\Filament\Resources\LostClaims\Pages;

use App\Filament\Resources\LostClaims\LostClaimResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLostClaim extends EditRecord
{
    protected static string $resource = LostClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
