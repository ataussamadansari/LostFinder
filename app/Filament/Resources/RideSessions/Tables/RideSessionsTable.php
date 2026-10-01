<?php

namespace App\Filament\Resources\RideSessions\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RideSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Ride #')
                    ->sortable(),
                TextColumn::make('driverProfile.vehicle_number')
                    ->label('Vehicle No.')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('driverProfile.user.name')
                    ->label('Driver')
                    ->searchable(),
                TextColumn::make('passenger.masked_alias')
                    ->label('Passenger')
                    ->searchable(),
                TextColumn::make('passenger.user.phone')
                    ->label('Passenger Phone')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Passenger phone copied'),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'active',
                        'success' => 'completed',
                        'danger'  => 'flagged',
                    ])
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Logged At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active'    => 'Active',
                        'completed' => 'Completed',
                        'flagged'   => 'Flagged',
                    ]),
            ])
            ->recordActions([
                Action::make('view_map')
                    ->label('GPS Map')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->color('info')
                    ->visible(fn ($record) => $record->scan_latitude && $record->scan_longitude)
                    ->url(fn ($record) => "https://www.google.com/maps?q={$record->scan_latitude},{$record->scan_longitude}", shouldOpenInNewTab: true),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
