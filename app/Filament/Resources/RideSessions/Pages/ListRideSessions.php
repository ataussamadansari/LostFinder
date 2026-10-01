<?php

namespace App\Filament\Resources\RideSessions\Pages;

use App\Filament\Resources\RideSessions\RideSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRideSessions extends ListRecords
{
    protected static string $resource = RideSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
