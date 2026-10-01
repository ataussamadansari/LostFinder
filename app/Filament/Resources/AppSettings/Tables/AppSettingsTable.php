<?php

namespace App\Filament\Resources\AppSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AppSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Setting Key')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Value')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->value),
                TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->colors([
                        'success' => 'sms',
                        'warning' => 'push',
                        'info'    => 'app_versions',
                        'danger'  => 'maintenance',
                        'primary' => 'support',
                    ])
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(35),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options([
                        'sms'          => 'SMS Gateway Settings',
                        'push'         => 'Push Notifications',
                        'app_versions' => 'App Versions',
                        'maintenance'  => 'Maintenance',
                        'support'      => 'Helplines',
                        'general'      => 'General',
                    ]),
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
