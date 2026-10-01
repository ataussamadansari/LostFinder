<?php

namespace App\Filament\Resources\AppSettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AppSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Setting Key')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(64)
                    ->placeholder('e.g. sms_api_key, fcm_server_key, sms_enabled'),
                Select::make('group')
                    ->label('Category Group')
                    ->options([
                        'sms'          => 'SMS Gateway Settings',
                        'push'         => 'Push Notifications (FCM)',
                        'app_versions' => 'App Versions & Update Controls',
                        'maintenance'  => 'App Maintenance Mode',
                        'support'      => 'Emergency Helplines & Support',
                        'general'      => 'General Configurations',
                    ])
                    ->default('general')
                    ->required(),
                Select::make('type')
                    ->label('Data Type')
                    ->options([
                        'string'  => 'String / Text',
                        'boolean' => 'Boolean (true/false or 1/0)',
                        'integer' => 'Integer Number',
                        'json'    => 'JSON Structured Data',
                    ])
                    ->default('string')
                    ->required(),
                Textarea::make('value')
                    ->label('Setting Value')
                    ->rows(3)
                    ->placeholder('Enter the configuration value here...'),
                TextInput::make('description')
                    ->label('Description / Context')
                    ->maxLength(255)
                    ->placeholder('Explains what this setting controls in the app'),
            ]);
    }
}
