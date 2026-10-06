<?php

namespace App\Providers;

use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\LostItemTicket;
use App\Models\User;
use App\Models\Vehicle;
use App\Policies\DriverDocumentPolicy;
use App\Policies\DriverPolicy;
use App\Policies\LostItemTicketPolicy;
use App\Policies\VehiclePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Driver::class, DriverPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(LostItemTicket::class, LostItemTicketPolicy::class);
        Gate::policy(DriverDocument::class, DriverDocumentPolicy::class);

        // Global Gate hook: Super admins bypass; custom admin permissions auto-grant
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            if ($user->hasPermission($ability)) {
                return true;
            }

            return null; // Fall through to specific policies
        });
    }
}
