<?php

namespace App\Filament\Widgets;

use App\Models\LostClaim;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestClaims extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Lost Belongings Reports')
            ->query(
                LostClaim::query()
                    ->with(['rideSession.driverProfile', 'rideSession.passenger.user'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('id')
                    ->label('Claim #'),
                TextColumn::make('rideSession.driverProfile.vehicle_number')
                    ->label('Vehicle No.')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('rideSession.passenger.user.name')
                    ->label('Passenger'),
                TextColumn::make('item_category')
                    ->badge(),
                TextColumn::make('item_description')
                    ->label('Description')
                    ->limit(35),
                ImageColumn::make('item_photo_url')
                    ->label('Photo')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('claim_status')
                    ->badge()
                    ->colors([
                        'warning' => 'reported',
                        'info'    => 'searching',
                        'primary' => 'found',
                        'success' => 'returned',
                        'danger'  => 'disputed',
                    ]),
                TextColumn::make('bounty_amount')
                    ->label('Bounty')
                    ->money('INR'),
                TextColumn::make('created_at')
                    ->label('Reported At')
                    ->dateTime(),
            ])
            ->paginated(false);
    }
}
