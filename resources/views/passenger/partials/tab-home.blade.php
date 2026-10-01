<!-- TAB 1: HOME & SCANNER (Responsive Grid on Tablet & Desktop) -->
<div x-show="activeTab === 'home'">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left / Main Column (Scan & Active Ride) -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Active Ride Ongoing Card (If user currently has an active ride) -->
            <template x-if="activeRide">
                <div class="p-5 sm:p-6 rounded-3xl bg-gradient-to-br from-indigo-950/80 via-slate-900 to-slate-900 border-2 border-indigo-500/50 shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl"></div>

                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div class="flex items-center space-x-2.5">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            </span>
                            <span class="text-xs sm:text-sm font-extrabold uppercase tracking-wider text-emerald-400">
                                Active Ride In Progress
                            </span>
                        </div>
                        <span class="text-xs font-mono text-indigo-300 bg-indigo-500/20 px-2.5 py-1 rounded-full font-semibold"
                              x-text="rideDuration"></span>
                    </div>

                    <div class="py-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xl sm:text-2xl font-black text-white" x-text="activeRide.vehicle_number"></h4>
                                <p class="text-xs text-slate-400 capitalize" x-text="`${activeRide.vehicle_type || 'Auto'} • Driver: ${activeRide.driver_name || 'Verified Driver'}`"></p>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 text-xl">
                                <i class="fa-solid fa-car-side"></i>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 text-xs text-slate-400 flex items-center space-x-2">
                            <i class="fa-solid fa-location-dot text-indigo-400 shrink-0"></i>
                            <span class="truncate" x-text="`Boarded at: Lat ${activeRide.scan_latitude?.toFixed(4)}, Lng ${activeRide.scan_longitude?.toFixed(4)}`"></span>
                        </div>
                    </div>

                    <div class="pt-2 flex flex-col sm:flex-row gap-3">
                        <button @click="completeRide(activeRide.ride_session_id || activeRide.id)"
                                :disabled="completingRide"
                                class="flex-1 py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs sm:text-sm transition flex items-center justify-center space-x-2 shadow-lg shadow-emerald-600/30">
                            <i class="fa-solid fa-flag-checkered"></i>
                            <span x-text="completingRide ? 'Ending Ride...' : 'Complete Ride Safely'"></span>
                        </button>
                        <button @click="openClaimModal(activeRide.ride_session_id || activeRide.id)"
                                class="py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-rose-400 font-bold text-xs sm:text-sm transition flex items-center justify-center space-x-2 border border-rose-500/30">
                            <i class="fa-solid fa-box-open"></i>
                            <span>Report Lost Item</span>
                        </button>
                    </div>
                </div>
            </template>

            <!-- Hero Scan Action Card -->
            <div class="p-6 sm:p-8 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl relative overflow-hidden">
                <div class="space-y-4">
                    <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold">
                        <i class="fa-solid fa-qrcode"></i>
                        <span>Boarding Verification</span>
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                            Boarding an Auto or Cab in Varanasi?
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-400 leading-relaxed max-w-xl">
                            Scan the vehicle's LostFinder QR sticker before departure. Your ride is timestamped with GPS in the city safety registry, guaranteeing recovery if anything is left behind.
                        </p>
                    </div>

                    <!-- Scan Trigger Buttons -->
                    <div class="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <button @click="openQrScanner()"
                                class="py-4 px-6 rounded-2xl bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm sm:text-base shadow-xl shadow-indigo-600/30 flex items-center justify-center space-x-2.5 transition">
                            <i class="fa-solid fa-camera text-lg"></i>
                            <span>Scan Driver QR Code</span>
                        </button>

                        <button @click="manualCodeModal = true"
                                class="py-4 px-5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs sm:text-sm border border-slate-700/80 flex items-center justify-center space-x-2 transition">
                            <i class="fa-solid fa-keyboard text-slate-400"></i>
                            <span>Enter Code Manually</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Action Chips (Safety Guide, Ghats Transit Helpline, Police) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div class="p-3.5 rounded-2xl bg-slate-900/80 border border-slate-800/80 flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <p class="text-[11px] text-slate-400">Tourist Safety</p>
                        <p class="text-xs font-bold text-white">UP Police 112</p>
                    </div>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-900/80 border border-slate-800/80 flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <div>
                        <p class="text-[11px] text-slate-400">Helpline</p>
                        <p class="text-xs font-bold text-white">1363 (Toll Free)</p>
                    </div>
                </div>

                <div class="col-span-2 sm:col-span-1 p-3.5 rounded-2xl bg-slate-900/80 border border-slate-800/80 flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <div>
                        <p class="text-[11px] text-slate-400">Ghats Transit</p>
                        <p class="text-xs font-bold text-white">Registered Auto</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column (Tourist Safety Tips & Live Claims Peek) -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Varanasi Safe Transit Advisory Card -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
                <div class="flex items-center space-x-2.5 text-indigo-400 font-bold text-sm">
                    <i class="fa-solid fa-lightbulb"></i>
                    <span>Safe Travel Checklist</span>
                </div>

                <ul class="space-y-3 text-xs sm:text-sm text-slate-300">
                    <li class="flex items-start space-x-2.5">
                        <i class="fa-solid fa-circle-check text-emerald-400 mt-1 shrink-0"></i>
                        <span>Scan driver QR code on the dashboard upon boarding.</span>
                    </li>
                    <li class="flex items-start space-x-2.5">
                        <i class="fa-solid fa-circle-check text-emerald-400 mt-1 shrink-0"></i>
                        <span>Keep note of your 6-digit Handover OTP if you report any lost belonging.</span>
                    </li>
                    <li class="flex items-start space-x-2.5">
                        <i class="fa-solid fa-circle-check text-emerald-400 mt-1 shrink-0"></i>
                        <span>Never share your secret OTP before you physically inspect your item.</span>
                    </li>
                    <li class="flex items-start space-x-2.5">
                        <i class="fa-solid fa-circle-check text-emerald-400 mt-1 shrink-0"></i>
                        <span>Tap SOS anytime for immediate location sharing with local authorities.</span>
                    </li>
                </ul>
            </div>

            <!-- Emergency SOS Card -->
            <div class="p-6 rounded-3xl bg-gradient-to-br from-rose-950/40 via-slate-900 to-slate-900 border border-rose-500/30 shadow-xl space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-rose-400 font-bold text-sm">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Emergency Assistance</span>
                    </div>
                    <span class="text-[10px] bg-rose-500/20 text-rose-300 px-2 py-0.5 rounded font-mono">24x7</span>
                </div>
                <p class="text-xs text-slate-300">
                    Feel unsafe or in an emergency? Trigger 1-tap alerts to Varanasi tourist police and emergency contacts.
                </p>
                <button @click="sosDrawer = true"
                        class="w-full py-3 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs sm:text-sm shadow-lg shadow-rose-900/50 flex items-center justify-center space-x-2 transition">
                    <i class="fa-solid fa-phone"></i>
                    <span>Open Emergency SOS Dialers</span>
                </button>
            </div>

        </div>

    </div>
</div>
