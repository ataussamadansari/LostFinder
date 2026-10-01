<?php

namespace App\Filament\Resources\RideSessions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RideSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('driver_profile_id')
                    ->relationship('driverProfile', 'vehicle_number')
                    ->label('Driver Vehicle')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('passenger_id')
                    ->relationship('passenger', 'masked_alias')
                    ->label('Passenger')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('status')
                    ->options([
                        'active'    => 'Active',
                        'completed' => 'Completed',
                        'flagged'   => 'Flagged (Claim Reported)',
                    ])
                    ->default('active')
                    ->required(),
                TextInput::make('scan_latitude')
                    ->label('GPS Latitude')
                    ->numeric(),
                TextInput::make('scan_longitude')
                    ->label('GPS Longitude')
                    ->numeric(),
            ]);
    }
}
