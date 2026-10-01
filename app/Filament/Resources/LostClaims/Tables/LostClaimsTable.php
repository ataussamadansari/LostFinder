<?php

namespace App\Filament\Resources\LostClaims\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LostClaimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Claim #')
                    ->sortable(),
                TextColumn::make('rideSession.driverProfile.vehicle_number')
                    ->label('Vehicle')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rideSession.passenger.user.name')
                    ->label('Passenger')
                    ->searchable(),
                TextColumn::make('item_category')
                    ->badge()
                    ->searchable(),
                TextColumn::make('item_description')
                    ->label('Description')
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->item_description),
                ImageColumn::make('item_photo_url')
                    ->label('Photo')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('handover_otp')
                    ->label('OTP')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('claim_status')
                    ->badge()
                    ->colors([
                        'warning' => 'reported',
                        'info'    => 'searching',
                        'primary' => 'found',
                        'success' => 'returned',
                        'danger'  => 'disputed',
                    ])
                    ->sortable(),
                TextColumn::make('bounty_amount')
                    ->label('Bounty')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Reported')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('claim_status')
                    ->options([
                        'reported'  => 'Reported',
                        'searching' => 'Searching',
                        'found'     => 'Found',
                        'returned'  => 'Returned',
                        'disputed'  => 'Disputed',
                    ]),
            ])
            ->recordActions([
                Action::make('resolve_returned')
                    ->label('Resolve Returned')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(fn ($record) => $record->claim_status !== 'returned')
                    ->action(function ($record) {
                        $record->update([
                            'claim_status' => 'returned',
                            'resolved_at'  => now(),
                        ]);
                        $record->rideSession?->update(['status' => 'completed']);
                    }),
                Action::make('flag_disputed')
                    ->label('Flag Dispute')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->color('danger')
                    ->visible(fn ($record) => !in_array($record->claim_status, ['returned', 'disputed']))
                    ->action(fn ($record) => $record->update(['claim_status' => 'disputed'])),
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
