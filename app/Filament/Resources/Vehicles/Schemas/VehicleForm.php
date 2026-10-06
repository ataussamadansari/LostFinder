<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->required(),
                TextInput::make('vehicle_code')
                    ->required(),
                TextInput::make('registration_number')
                    ->required(),
                Select::make('vehicle_type')
                    ->options([
            'cab' => 'Cab',
            'taxi' => 'Taxi',
            'auto' => 'Auto',
            'e_rickshaw' => 'E rickshaw',
            'bus' => 'Bus',
            'tourist_vehicle' => 'Tourist vehicle',
            'other' => 'Other',
        ])
                    ->required(),
                TextInput::make('make')
                    ->default(null),
                TextInput::make('model')
                    ->default(null),
                TextInput::make('color')
                    ->default(null),
                Select::make('verification_status')
                    ->options([
            'pending' => 'Pending',
            'under_review' => 'Under review',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            'suspended' => 'Suspended',
        ])
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('verified_at'),
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'blocked' => 'Blocked'])
                    ->default('active')
                    ->required(),
            ]);
    }
}
