<?php

namespace App\Filament\Resources\SystemSettings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SystemSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('group')
                    ->required(),
                TextInput::make('key')
                    ->required(),
                Textarea::make('value')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('type')
                    ->required()
                    ->default('string'),
                TextInput::make('label')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('is_public')
                    ->required(),
                Toggle::make('is_encrypted')
                    ->required(),
            ]);
    }
}
