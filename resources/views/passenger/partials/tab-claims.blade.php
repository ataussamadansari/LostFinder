<!-- TAB 2: LOST ITEMS & CLAIMS -->
<div x-show="activeTab === 'claims'" class="space-y-6">

    <!-- Section Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-white flex items-center space-x-2.5">
                <i class="fa-solid fa-box-open text-indigo-400"></i>
                <span>Your Lost Item Claims</span>
            </h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Track status and view your secret 6-digit Handover OTP for recovery.
            </p>
        </div>

        <template x-if="auth.token">
            <button @click="fetchTouristClaims()"
                    :disabled="loadingClaims"
                    class="self-start sm:self-auto px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-800 flex items-center space-x-2 transition">
                <i class="fa-solid fa-rotate-right" :class="loadingClaims ? 'fa-spin' : ''"></i>
                <span>Refresh Claims</span>
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
                    Sign in with your phone or 1-tap demo access to view and manage your lost items.
                </p>
            </div>
            <button @click="loginModal = true"
                    class="py-3 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs sm:text-sm shadow-lg shadow-indigo-600/30 transition">
                Sign In to View Claims
            </button>
        </div>
    </template>

    <!-- Signed In: Claims Feed -->
    <template x-if="auth.token">
        <div>
            <!-- Loading Skeleton -->
            <div x-show="loadingClaims" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 animate-pulse space-y-3">
                    <div class="h-4 bg-slate-800 rounded w-1/3"></div>
                    <div class="h-3 bg-slate-800 rounded w-2/3"></div>
                    <div class="h-8 bg-slate-800 rounded w-full"></div>
                </div>
            </div>

            <!-- Empty State -->
            <div x-show="!loadingClaims && claimsList.length === 0"
                 class="p-10 rounded-3xl bg-slate-900 border border-slate-800 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-slate-800 text-slate-400 flex items-center justify-center mx-auto text-xl">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <h4 class="text-base font-bold text-white">No Lost Claims Filed Yet</h4>
                <p class="text-xs sm:text-sm text-slate-400 max-w-sm mx-auto">
                    If you left an item in a recent ride, go to the <strong>Ride History</strong> tab and tap "Report Lost Item".
                </p>
                <button @click="activeTab = 'rides'"
                        class="px-4 py-2 rounded-xl bg-slate-800 text-indigo-300 text-xs font-semibold hover:bg-slate-700 transition">
                    View Ride History
                </button>
            </div>

            <!-- Claims Grid -->
            <div x-show="!loadingClaims && claimsList.length > 0"
                 class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <template x-for="claim in claimsList" :key="claim.id">
                    <div class="p-5 sm:p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 shadow-xl relative overflow-hidden">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div class="flex items-center space-x-2">
                                <span class="text-xs sm:text-sm font-bold text-white" x-text="claim.item_category"></span>
                                <span class="text-[10px] text-slate-400 font-mono" x-text="`#${claim.id}`"></span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                  :class="{
                                      'bg-amber-500/20 text-amber-400 border border-amber-500/30': claim.claim_status === 'reported',
                                      'bg-blue-500/20 text-blue-400 border border-blue-500/30': claim.claim_status === 'searching',
                                      'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': claim.claim_status === 'found',
                                      'bg-purple-500/20 text-purple-400 border border-purple-500/30': claim.claim_status === 'returned',
                                      'bg-rose-500/20 text-rose-400 border border-rose-500/30': claim.claim_status === 'disputed'
                                  }"
                                  x-text="claim.claim_status">
                            </span>
                        </div>

                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed" x-text="claim.item_description"></p>

                        <!-- Secret 6-Digit Handover OTP Card -->
                        <div class="p-3.5 rounded-2xl bg-indigo-950/60 border border-indigo-500/40 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-semibold text-indigo-300 flex items-center space-x-1.5">
                                    <i class="fa-solid fa-key text-xs"></i>
                                    <span>Secret Handover OTP</span>
                                </span>
                                <span class="text-[10px] bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded font-mono font-bold"
                                      x-text="claim.handover_otp ? claim.handover_otp : 'Hidden'"></span>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-normal">
                                Share this 6-digit OTP with the driver <strong>only when you physically receive your item</strong>.
                            </p>
                        </div>

                        <!-- Driver Info if linked -->
                        <template x-if="claim.ride_session?.driver_profile">
                            <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 text-xs text-slate-400 flex items-center justify-between">
                                <span x-text="`Vehicle: ${claim.ride_session.driver_profile.vehicle_number}`"></span>
                                <span x-text="claim.ride_session.driver_profile.user?.name"></span>
                            </div>
                        </template>

                        <!-- Actions -->
                        <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-800/80">
                            <span class="text-slate-500 font-mono text-[11px]" x-text="formatDate(claim.created_at)"></span>
                            <template x-if="claim.claim_status === 'reported'">
                                <button @click="cancelClaim(claim.id)"
                                        class="text-rose-400 hover:text-rose-300 font-semibold flex items-center space-x-1 transition">
                                    <i class="fa-solid fa-ban"></i>
                                    <span>Cancel Claim</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

</div>
