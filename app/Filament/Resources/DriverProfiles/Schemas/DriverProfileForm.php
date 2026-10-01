<?php

namespace App\Filament\Resources\DriverProfiles\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DriverProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name', fn ($query) => $query->where('role', 'driver'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Driver User Account'),
                TextInput::make('vehicle_number')
                    ->label('Vehicle Registration No.')
                    ->required()
                    ->maxLength(25),
                Select::make('vehicle_type')
                    ->options([
                        'auto'       => 'Auto Rickshaw',
                        'e_rickshaw' => 'E-Rickshaw',
                        'cab'        => 'Cab / Taxi',
                        'bike'       => 'Bike / Two Wheeler',
                    ])
                    ->default('auto')
                    ->required(),
                TextInput::make('license_number')
                    ->label('Driving License No.')
                    ->maxLength(50),
                FileUpload::make('rc_photo_url')
                    ->label('Vehicle RC Photo')
                    ->image()
                    ->disk('public')
                    ->directory('drivers/rc'),
                TextInput::make('qr_code_token')
                    ->label('QR Code Token')
                    ->disabled()
                    ->dehydrated(false),
                Toggle::make('is_verified')
                    ->label('Driver / Vehicle Verified')
                    ->default(false),
                TextInput::make('total_trips')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
