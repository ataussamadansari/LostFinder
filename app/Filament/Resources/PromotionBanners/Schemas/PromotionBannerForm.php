<?php

namespace App\Filament\Resources\PromotionBanners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PromotionBannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Campaign / Ad Title')
                    ->required()
                    ->maxLength(120),
                TextInput::make('subtitle')
                    ->label('Subtitle / Tagline')
                    ->maxLength(255),
                FileUpload::make('image_url')
                    ->label('Banner Creative Image')
                    ->image()
                    ->disk('public')
                    ->directory('promotions')
                    ->required(),
                Select::make('placement')
                    ->label('Display Placement')
                    ->options([
                        'tourist_home' => 'Tourist App - Home Screen',
                        'driver_home'  => 'Driver App - Dashboard',
                        'ride_screen'  => 'Active Ride In-Progress Screen',
                        'claim_screen' => 'Claim Filed Success Screen',
                    ])
                    ->default('tourist_home')
                    ->required(),
                Select::make('action_type')
                    ->label('Click Action')
                    ->options([
                        'url'    => 'External Web URL',
                        'screen' => 'In-App Deep Link',
                        'none'   => 'None (Static Banner)',
                    ])
                    ->default('url')
                    ->required(),
                TextInput::make('action_target')
                    ->label('Target Link / URL')
                    ->placeholder('https://example.com/promo or lostfinder://rides'),
                TextInput::make('priority')
                    ->label('Display Priority')
                    ->numeric()
                    ->default(0)
                    ->helperText('Banners with higher numbers are shown first.'),
                Toggle::make('is_active')
                    ->label('Active / Running')
                    ->default(true),
                DateTimePicker::make('starts_at')
                    ->label('Start Date & Time'),
                DateTimePicker::make('expires_at')
                    ->label('Expiry Date & Time'),
            ]);
    }
}
