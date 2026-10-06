<?php

namespace App\Filament\Resources\Reports\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reporter.name')
                    ->label('Reporter'),
                TextEntry::make('reportedUser.name')
                    ->label('Reported user')
                    ->placeholder('-'),
                TextEntry::make('vehicle.id')
                    ->label('Vehicle')
                    ->placeholder('-'),
                TextEntry::make('ticket.id')
                    ->label('Ticket')
                    ->placeholder('-'),
                TextEntry::make('type'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('resolved_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('resolved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
