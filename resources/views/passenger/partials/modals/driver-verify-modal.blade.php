<!-- 3. Driver Verification & Start Ride Modal -->
<div x-show="driverVerificationModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
     x-transition>
    <div class="w-full max-w-sm sm:max-w-md rounded-3xl bg-slate-900 border border-slate-800 p-6 space-y-5 shadow-2xl relative overflow-hidden">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <h3 class="text-sm sm:text-base font-bold text-white">Driver Verification</h3>
            </div>
            <button @click="driverVerificationModal = false" class="text-slate-400 hover:text-white p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <template x-if="scannedDriver">
            <div class="space-y-4">
                <!-- Driver Info Card -->
                <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/80 flex items-center space-x-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center text-white text-xl font-bold shrink-0 shadow-md shadow-indigo-600/30">
                        <template x-if="scannedDriver.profile_picture_url">
                            <img :src="scannedDriver.profile_picture_url" class="w-full h-full object-cover rounded-2xl">
                        </template>
                        <template x-if="!scannedDriver.profile_picture_url">
                            <span x-text="scannedDriver.driver_name?.charAt(0) || 'D'"></span>
                        </template>
                    </div>
                    <div class="space-y-0.5 overflow-hidden">
                        <h4 class="text-base font-bold text-white truncate" x-text="scannedDriver.driver_name"></h4>
                        <p class="text-xs font-mono font-semibold text-indigo-400" x-text="scannedDriver.vehicle_number"></p>
                        <p class="text-[11px] text-slate-400 capitalize" x-text="`${scannedDriver.vehicle_type || 'Auto'} • ${scannedDriver.total_trips || 0} Safe Trips Completed`"></p>
                    </div>
                </div>

                <!-- Safety Badge -->
                <div class="p-3.5 rounded-xl bg-emerald-950/20 border border-emerald-500/30 flex items-center space-x-3 text-xs sm:text-sm text-emerald-300">
                    <i class="fa-solid fa-shield-halved text-lg text-emerald-400 shrink-0"></i>
                    <span>Verified in City Transit Database. GPS location timestamped with this ride.</span>
                </div>

                <!-- Start Ride Button -->
                <button @click="startAndLogRide()"
                        :disabled="startingRide"
                        class="w-full py-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm sm:text-base shadow-xl shadow-emerald-600/30 flex items-center justify-center space-x-2 transition">
                    <i class="fa-solid fa-route"></i>
                    <span x-text="startingRide ? 'Logging Ride with GPS...' : 'Confirm & Start Safe Ride'"></span>
                </button>
            </div>
        </template>
    </div>
</div>
