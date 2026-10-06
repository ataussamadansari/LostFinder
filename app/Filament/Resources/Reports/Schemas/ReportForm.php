<?php

namespace App\Filament\Resources\Reports\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('reporter_id')
                    ->relationship('reporter', 'name')
                    ->required(),
                Select::make('reported_user_id')
                    ->relationship('reportedUser', 'name')
                    ->default(null),
                Select::make('vehicle_id')
                    ->relationship('vehicle', 'id')
                    ->default(null),
                Select::make('ticket_id')
                    ->relationship('ticket', 'id')
                    ->default(null),
                TextInput::make('type')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
            'open' => 'Open',
            'investigating' => 'Investigating',
            'resolved' => 'Resolved',
            'rejected' => 'Rejected',
        ])
                    ->default('open')
                    ->required(),
                TextInput::make('resolved_by')
                    ->numeric()
                    ->default(null),
                DateTimePicker::make('resolved_at'),
            ]);
    }
}
