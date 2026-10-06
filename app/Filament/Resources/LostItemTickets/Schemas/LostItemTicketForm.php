<?php

namespace App\Filament\Resources\LostItemTickets\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LostItemTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('ticket_number')
                    ->required(),
                Select::make('journey_id')
                    ->relationship('journey', 'id')
                    ->required(),
                Select::make('passenger_id')
                    ->relationship('passenger', 'name')
                    ->required(),
                Select::make('driver_id')
                    ->relationship('driver', 'id')
                    ->required(),
                Select::make('vehicle_id')
                    ->relationship('vehicle', 'id')
                    ->required(),
                TextInput::make('lost_item_id')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options([
            'created' => 'Created',
            'driver_notified' => 'Driver notified',
            'searching' => 'Searching',
            'item_found' => 'Item found',
            'recovery_pending' => 'Recovery pending',
            'handed_over' => 'Handed over',
            'closed' => 'Closed',
            'not_found' => 'Not found',
            'cancelled' => 'Cancelled',
            'disputed' => 'Disputed',
            'escalated' => 'Escalated',
        ])
                    ->default('created')
                    ->required(),
                DateTimePicker::make('reported_at')
                    ->required(),
                DateTimePicker::make('closed_at'),
            ]);
    }
}
