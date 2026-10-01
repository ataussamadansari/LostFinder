<?php

namespace App\Filament\Resources\DriverProfiles\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DriverProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Driver Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Driver phone copied'),
                TextColumn::make('vehicle_number')
                    ->label('Vehicle No.')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('vehicle_type')
                    ->badge()
                    ->colors([
                        'warning' => 'auto',
                        'success' => 'e_rickshaw',
                        'info'    => 'cab',
                        'primary' => 'bike',
                    ])
                    ->sortable(),
                TextColumn::make('license_number')
                    ->label('License No.')
                    ->searchable(),
                ImageColumn::make('rc_photo_url')
                    ->label('RC Photo')
                    ->disk('public')
                    ->circular(),
                IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('total_trips')
                    ->label('Trips')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_verified')
                    ->label('Verification Status'),
                SelectFilter::make('vehicle_type')
                    ->options([
                        'auto'       => 'Auto Rickshaw',
                        'e_rickshaw' => 'E-Rickshaw',
                        'cab'        => 'Cab / Taxi',
                        'bike'       => 'Bike / Two Wheeler',
                    ]),
            ])
            ->recordActions([
                Action::make('toggle_verification')
                    ->label(fn ($record) => $record->is_verified ? 'Unverify' : 'Verify Driver')
                    ->color(fn ($record) => $record->is_verified ? 'danger' : 'success')
                    ->icon(fn ($record) => $record->is_verified ? Heroicon::OutlinedXCircle : Heroicon::OutlinedCheckCircle)
                    ->action(fn ($record) => $record->update(['is_verified' => !$record->is_verified])),
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
