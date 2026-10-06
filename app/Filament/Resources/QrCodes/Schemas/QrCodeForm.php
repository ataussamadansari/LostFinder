<?php

namespace App\Filament\Resources\QrCodes\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QrCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('vehicle_id')
                    ->relationship('vehicle', 'id')
                    ->required(),
                TextInput::make('token')
                    ->required(),
                TextInput::make('version')
                    ->required()
                    ->numeric()
                    ->default(1),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'active' => 'Active', 'disabled' => 'Disabled', 'revoked' => 'Revoked'])
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('activated_at'),
                DateTimePicker::make('revoked_at'),
            ]);
    }
}
