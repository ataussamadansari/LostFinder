<?php

namespace App\Filament\Resources\LostItemTickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LostItemTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('ticket_number'),
                TextEntry::make('journey.id')
                    ->label('Journey'),
                TextEntry::make('passenger.name')
                    ->label('Passenger'),
                TextEntry::make('driver.id')
                    ->label('Driver'),
                TextEntry::make('vehicle.id')
                    ->label('Vehicle'),
                TextEntry::make('lost_item_id')
                    ->numeric(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('reported_at')
                    ->dateTime(),
                TextEntry::make('closed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
