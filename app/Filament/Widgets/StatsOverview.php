<?php

namespace App\Filament\Widgets;

use App\Models\DriverProfile;
use App\Models\LostClaim;
use App\Models\RideSession;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalDrivers = DriverProfile::count();
        $unverifiedDrivers = DriverProfile::where('is_verified', false)->count();

        $totalRides = RideSession::count();
        $activeRides = RideSession::where('status', 'active')->count();

        $totalClaims = LostClaim::count();
        $openClaims = LostClaim::whereIn('claim_status', ['reported', 'searching', 'found', 'disputed'])->count();

        $returnedClaims = LostClaim::where('claim_status', 'returned')->count();
        $totalBounties = LostClaim::where('claim_status', 'returned')->sum('bounty_amount');

        return [
            Stat::make('Total Registered Drivers', (string) $totalDrivers)
                ->description($unverifiedDrivers . ' pending KYC verification')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($unverifiedDrivers > 0 ? 'warning' : 'success'),

            Stat::make('Total Rides Tracked', (string) $totalRides)
                ->description($activeRides . ' active rides right now')
                ->descriptionIcon(Heroicon::OutlinedMapPin)
                ->color('primary'),

            Stat::make('Lost Items Reported', (string) $totalClaims)
                ->description($openClaims . ' unresolved cases')
                ->descriptionIcon(Heroicon::OutlinedExclamationCircle)
                ->color($openClaims > 0 ? 'danger' : 'success'),

            Stat::make('Successfully Returned', (string) $returnedClaims)
                ->description('₹' . number_format((float) $totalBounties, 2) . ' bounties awarded')
                ->descriptionIcon(Heroicon::OutlinedCheckBadge)
                ->color('success'),
        ];
    }
}
