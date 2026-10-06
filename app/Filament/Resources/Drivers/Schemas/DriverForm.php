<?php

namespace App\Filament\Resources\Drivers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DriverForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('driver_code')
                    ->required(),
                Select::make('verification_status')
                    ->options([
            'pending' => 'Pending',
            'under_review' => 'Under review',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            'suspended' => 'Suspended',
            'blocked' => 'Blocked',
        ])
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('verified_at'),
                TextInput::make('rating_avg')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('rating_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
