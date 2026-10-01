<!-- TAB 3: RIDES HISTORY -->
<div x-show="activeTab === 'rides'" class="space-y-6">

    <!-- Section Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-white flex items-center space-x-2.5">
                <i class="fa-solid fa-route text-indigo-400"></i>
                <span>Your Travel History</span>
            </h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                All rides scanned and logged during your Varanasi visit.
            </p>
        </div>

        <template x-if="auth.token">
            <button @click="fetchTouristRides()"
                    :disabled="loadingRides"
                    class="self-start sm:self-auto px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-800 flex items-center space-x-2 transition">
                <i class="fa-solid fa-rotate-right" :class="loadingRides ? 'fa-spin' : ''"></i>
                <span>Refresh History</span>
            </button>
        </template>
    </div>

    <!-- Not Signed In Prompt -->
    <template x-if="!auth.token">
        <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800 text-center space-y-4 max-w-md mx-auto">
            <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center mx-auto text-2xl">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div class="space-y-1">
                <h3 class="text-base sm:text-lg font-bold text-white">Sign In Required</h3>
                <p class="text-xs sm:text-sm text-slate-400">
                    Sign in with your phone or 1-tap demo access to see your timestamped rides.
                </p>
            </div>
            <button @click="loginModal = true"
                    class="py-3 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs sm:text-sm shadow-lg shadow-indigo-600/30 transition">
                Sign In to View Rides
            </button>
        </div>
    </template>

    <!-- Signed In: Rides List -->
    <template x-if="auth.token">
        <div>
            <!-- Loading Skeleton -->
            <div x-show="loadingRides" class="space-y-4">
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 animate-pulse space-y-3">
                    <div class="h-4 bg-slate-800 rounded w-1/4"></div>
                    <div class="h-3 bg-slate-800 rounded w-1/2"></div>
                </div>
            </div>

            <!-- Empty State -->
            <div x-show="!loadingRides && ridesList.length === 0"
                 class="p-10 rounded-3xl bg-slate-900 border border-slate-800 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-slate-800 text-slate-400 flex items-center justify-center mx-auto text-xl">
                    <i class="fa-solid fa-route"></i>
                </div>
                <h4 class="text-base font-bold text-white">No Rides Logged Yet</h4>
                <p class="text-xs sm:text-sm text-slate-400 max-w-sm mx-auto">
                    When you board any auto or cab, scan the driver's QR code from the home screen to log your ride.
                </p>
                <button @click="activeTab = 'home'"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md transition">
                    Scan Driver QR
                </button>
            </div>

            <!-- Rides Grid -->
            <div x-show="!loadingRides && ridesList.length > 0"
                 class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="ride in ridesList" :key="ride.id">
                    <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 space-y-3 shadow-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-base font-bold text-white" x-text="ride.driver_profile?.vehicle_number || 'Vehicle Logged'"></h4>
                                <p class="text-xs text-slate-400" x-text="`Driver: ${ride.driver_profile?.user?.name || 'Verified'}`"></p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
                                  :class="{
                                      'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': ride.status === 'active',
                                      'bg-slate-700/50 text-slate-400': ride.status === 'completed',
                                      'bg-rose-500/20 text-rose-400 border border-rose-500/30': ride.status === 'flagged'
                                  }"
                                  x-text="ride.status">
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs text-slate-400 pt-3 border-t border-slate-800/80">
                            <span class="font-mono" x-text="formatDate(ride.created_at)"></span>
                            <button @click="openClaimModal(ride.id)"
                                    class="text-rose-400 hover:text-rose-300 font-semibold flex items-center space-x-1.5 transition">
                                <i class="fa-solid fa-box-open"></i>
                                <span>Report Lost Item</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

</div>
