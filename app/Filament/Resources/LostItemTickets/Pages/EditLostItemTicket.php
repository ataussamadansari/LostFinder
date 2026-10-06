<?php

namespace App\Filament\Resources\LostItemTickets\Pages;

use App\Filament\Resources\LostItemTickets\LostItemTicketResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLostItemTicket extends EditRecord
{
    protected static string $resource = LostItemTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
