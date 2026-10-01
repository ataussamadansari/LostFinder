<?php

namespace App\Filament\Resources\LostClaims\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LostClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ride_session_id')
                    ->relationship('rideSession', 'id')
                    ->label('Ride Session ID')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('item_category')
                    ->label('Item Category')
                    ->required()
                    ->maxLength(50),
                Textarea::make('item_description')
                    ->label('Detailed Description')
                    ->rows(4)
                    ->required(),
                FileUpload::make('item_photo_url')
                    ->label('Item Photo')
                    ->image()
                    ->disk('public')
                    ->directory('claims'),
                TextInput::make('handover_otp')
                    ->label('Secret Handover OTP')
                    ->required()
                    ->maxLength(6),
                Select::make('claim_status')
                    ->label('Claim Status')
                    ->options([
                        'reported'  => 'Reported',
                        'searching' => 'Searching',
                        'found'     => 'Found',
                        'returned'  => 'Returned & Handed Over',
                        'disputed'  => 'Disputed / Under Investigation',
                    ])
                    ->default('reported')
                    ->required(),
                TextInput::make('bounty_amount')
                    ->label('Bounty Amount (₹)')
                    ->numeric()
                    ->prefix('₹')
                    ->default(0.00),
                DateTimePicker::make('resolved_at')
                    ->label('Resolved At'),
            ]);
    }
}
