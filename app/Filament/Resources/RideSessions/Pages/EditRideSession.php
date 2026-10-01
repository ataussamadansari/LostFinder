<?php

namespace App\Filament\Resources\RideSessions\Pages;

use App\Filament\Resources\RideSessions\RideSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRideSession extends EditRecord
{
    protected static string $resource = RideSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
