<?php

namespace App\Filament\Widgets;

use App\Models\Driver;
use App\Models\Journey;
use App\Models\LostItemTicket;
use App\Models\Report;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $verifiedDrivers = Driver::where('verification_status', 'verified')->count();
        $pendingDrivers = Driver::where('verification_status', 'pending')->count();
        $verifiedVehicles = Vehicle::where('verification_status', 'verified')->count();
        $activeJourneys = Journey::where('status', 'active')->count();
        $openTickets = LostItemTicket::whereIn('status', [
            'open', 'driver_searching', 'item_found', 'handover_pending', 'disputed'
        ])->count();
        $openReports = Report::where('status', 'open')->count();
        $totalUsers = User::count();

        return [
            Stat::make('Total Platform Users', number_format($totalUsers))
                ->description('Registered accounts')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Verified Drivers', number_format($verifiedDrivers))
                ->description($pendingDrivers . ' pending review')
                ->descriptionIcon('heroicon-m-identification')
                ->color($pendingDrivers > 0 ? 'warning' : 'success'),

            Stat::make('Verified Vehicles', number_format($verifiedVehicles))
                ->description('Fleet operational')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),

            Stat::make('Active Journeys', number_format($activeJourneys))
                ->description('In progress')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('success'),

            Stat::make('Open Lost Tickets', number_format($openTickets))
                ->description('Active incidents')
                ->descriptionIcon('heroicon-m-ticket')
                ->color($openTickets > 0 ? 'danger' : 'gray'),

            Stat::make('Open Reports', number_format($openReports))
                ->description('Awaiting investigation')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($openReports > 0 ? 'danger' : 'gray'),
        ];
    }
}
