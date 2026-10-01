<?php

namespace App\Filament\Resources\PromotionBanners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PromotionBannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Banner')
                    ->disk('public'),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('placement')
                    ->badge()
                    ->colors([
                        'info'    => 'tourist_home',
                        'warning' => 'driver_home',
                        'success' => 'ride_screen',
                        'primary' => 'claim_screen',
                    ])
                    ->sortable(),
                TextColumn::make('impressions')
                    ->label('Views')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('clicks')
                    ->label('Clicks')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('priority')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('placement')
                    ->options([
                        'tourist_home' => 'Tourist Home',
                        'driver_home'  => 'Driver Dashboard',
                        'ride_screen'  => 'Ride Screen',
                        'claim_screen' => 'Claim Screen',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->recordActions([
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
