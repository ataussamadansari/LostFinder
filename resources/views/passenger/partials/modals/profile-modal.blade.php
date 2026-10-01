<!-- User Profile Management & Logout Modal -->
<div x-show="profileModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md"
     x-transition>
    <div @click.away="profileModal = false"
         class="w-full max-w-sm sm:max-w-md rounded-3xl bg-slate-900 border border-slate-800 p-6 sm:p-8 space-y-5 shadow-2xl relative">

        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <div class="flex items-center space-x-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center text-base">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-white">Tourist Profile</h3>
                    <p class="text-[11px] text-slate-400">Manage identity & emergency details</p>
                </div>
            </div>
            <button @click="profileModal = false" class="text-slate-400 hover:text-white p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Identity Card -->
        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/80 flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400">Masked Privacy Alias</span>
                <p class="text-sm font-mono font-extrabold text-white" x-text="profileData.masked_alias || 'Passenger #VARANASI'"></p>
                <p class="text-[11px] text-slate-400" x-text="`Verified Phone: ${auth.user?.phone || 'Not available'}`"></p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 text-xl font-bold">
                <i class="fa-solid fa-user-check"></i>
            </div>
        </div>

        <!-- Tourist Stats Overview -->
        <div class="grid grid-cols-2 gap-3">
            <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800/80 text-center">
                <span class="text-xs text-slate-400">Total Rides</span>
                <p class="text-lg font-black text-white" x-text="profileData.total_rides || ridesList.length"></p>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800/80 text-center">
                <span class="text-xs text-slate-400">Lost Claims</span>
                <p class="text-lg font-black text-white" x-text="profileData.total_claims || claimsList.length"></p>
            </div>
        </div>

        <!-- Editable Profile Fields -->
        <div class="space-y-3.5">
            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-300">Your Full Name</label>
                <input type="text"
                       x-model="profileForm.name"
                       placeholder="Enter your name"
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-300">Emergency Contact Number</label>
                <input type="tel"
                       x-model="profileForm.emergency_contact_phone"
                       placeholder="e.g. +91 98765 43210"
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 font-mono">
                <p class="text-[10px] text-slate-400">Alerted if you press SOS during a ride.</p>
            </div>

            <!-- Save Profile Button -->
            <button @click="updateProfile()"
                    :disabled="savingProfile"
                    class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-indigo-600/30 transition flex items-center justify-center space-x-2">
                <i class="fa-solid fa-floppy-disk text-xs"></i>
                <span x-text="savingProfile ? 'Saving Profile...' : 'Save Profile Changes'"></span>
            </button>
        </div>

        <!-- Logout Action Button -->
        <div class="pt-2 border-t border-slate-800">
            <button @click="logout(); profileModal = false;"
                    class="w-full py-3 rounded-xl bg-rose-600/15 hover:bg-rose-600/25 border border-rose-500/30 text-rose-400 font-bold text-xs sm:text-sm transition flex items-center justify-center space-x-2">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Sign Out from LostFinder</span>
            </button>
        </div>

    </div>
</div>
