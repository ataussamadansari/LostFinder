<?php

namespace App\Filament\Resources\LostItemTickets\Pages;

use App\Filament\Resources\LostItemTickets\LostItemTicketResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLostItemTicket extends ViewRecord
{
    protected static string $resource = LostItemTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
