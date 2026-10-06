<?php

namespace App\Filament\Resources\LostItemTickets;

use App\Filament\Resources\LostItemTickets\Pages\CreateLostItemTicket;
use App\Filament\Resources\LostItemTickets\Pages\EditLostItemTicket;
use App\Filament\Resources\LostItemTickets\Pages\ListLostItemTickets;
use App\Filament\Resources\LostItemTickets\Pages\ViewLostItemTicket;
use App\Filament\Resources\LostItemTickets\Schemas\LostItemTicketForm;
use App\Filament\Resources\LostItemTickets\Schemas\LostItemTicketInfolist;
use App\Filament\Resources\LostItemTickets\Tables\LostItemTicketsTable;
use App\Models\LostItemTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LostItemTicketResource extends Resource
{
    protected static ?string $model = LostItemTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|\UnitEnum|null $navigationGroup = 'Lost & Found';

    protected static ?string $recordTitleAttribute = 'ticket_number';

    public static function form(Schema $schema): Schema
    {
        return LostItemTicketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LostItemTicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LostItemTicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLostItemTickets::route('/'),
            'create' => CreateLostItemTicket::route('/create'),
            'view' => ViewLostItemTicket::route('/{record}'),
            'edit' => EditLostItemTicket::route('/{record}/edit'),
        ];
    }
}
