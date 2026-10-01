<?php

namespace App\Filament\Resources\ItemCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ItemCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Category Name')
                    ->required()
                    ->maxLength(60)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->label('Unique Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(60),
                TextInput::make('icon')
                    ->label('Icon Name')
                    ->placeholder('e.g. phone, wallet, briefcase, key')
                    ->maxLength(60),
                TextInput::make('suggested_bounty')
                    ->label('Suggested Bounty Amount')
                    ->prefix('₹')
                    ->numeric()
                    ->default(200.00),
                TextInput::make('priority')
                    ->label('Display Order')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Active in Apps')
                    ->default(true),
            ]);
    }
}
