<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\Pages\CreateUser;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(100),
                TextInput::make('country_code')
                    ->required()
                    ->default('+91')
                    ->maxLength(5),
                TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(20),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->maxLength(120),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn ($livewire) => $livewire instanceof CreateUser),
                FileUpload::make('profile_picture')
                    ->image()
                    ->disk('public')
                    ->directory('avatars')
                    ->avatar(),
                TextInput::make('emergency_contact_phone')
                    ->tel()
                    ->maxLength(20),
                Select::make('role')
                    ->options([
                        'admin'   => 'Admin',
                        'driver'  => 'Driver',
                        'tourist' => 'Tourist',
                    ])
                    ->default('tourist')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Account Active')
                    ->default(true)
                    ->required(),
                DateTimePicker::make('phone_verified_at'),
            ]);
    }
}
