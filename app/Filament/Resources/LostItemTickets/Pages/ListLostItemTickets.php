<?php

namespace App\Filament\Resources\LostItemTickets\Pages;

use App\Filament\Resources\LostItemTickets\LostItemTicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLostItemTickets extends ListRecords
{
    protected static string $resource = LostItemTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
