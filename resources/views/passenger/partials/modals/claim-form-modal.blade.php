<!-- 4. Report Lost Item Claim Modal -->
<div x-show="claimModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
     x-transition>
    <div @click.away="claimModal = false"
         class="w-full max-w-sm sm:max-w-md rounded-3xl bg-slate-900 border border-slate-800 p-6 space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-sm sm:text-base font-bold text-white flex items-center space-x-2">
                <i class="fa-solid fa-box-open text-rose-400"></i>
                <span>Report Lost Belonging</span>
            </h3>
            <button @click="claimModal = false" class="text-slate-400 hover:text-white p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="space-y-4">
            <!-- Category Selection Chips -->
            <div class="space-y-2">
                <label class="text-xs sm:text-sm font-semibold text-slate-300">Item Category</label>
                <div class="flex flex-wrap gap-2">
                    <template x-for="cat in categories" :key="cat.id">
                        <button type="button"
                                @click="claimForm.category = cat.name"
                                class="px-3 py-1.5 rounded-xl text-xs sm:text-sm font-medium border transition"
                                :class="claimForm.category === cat.name
                                    ? 'bg-indigo-600 border-indigo-500 text-white font-semibold'
                                    : 'bg-slate-950 border-slate-800 text-slate-300 hover:border-slate-700'">
                            <span x-text="cat.name"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Item Description -->
            <div class="space-y-2">
                <label class="text-xs sm:text-sm font-semibold text-slate-300">Description & Distinct Marks</label>
                <textarea x-model="claimForm.description"
                          rows="3"
                          placeholder="Color, brand, where you left it (e.g. Back seat left side, Black leather wallet with cards)"
                          class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500"></textarea>
            </div>

            <!-- Cash Bounty / Reward (Optional) -->
            <div class="space-y-2">
                <label class="text-xs sm:text-sm font-semibold text-slate-300 flex items-center justify-between">
                    <span>Cash Bounty / Reward (Optional)</span>
                    <span class="text-[11px] text-amber-400">Boosts driver return motivation</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-3 text-xs sm:text-sm text-slate-500 font-bold">₹</span>
                    <input type="number"
                           x-model="claimForm.bounty"
                           placeholder="e.g. 500"
                           class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            <!-- Submit Claim Button -->
            <button @click="submitLostClaim()"
                    :disabled="!claimForm.category || !claimForm.description.trim() || submittingClaim"
                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-rose-600 to-indigo-600 hover:from-rose-500 hover:to-indigo-500 disabled:opacity-50 text-white text-xs sm:text-sm font-bold shadow-xl shadow-rose-600/30 transition flex items-center justify-center space-x-2">
                <i class="fa-solid fa-paper-plane"></i>
                <span x-text="submittingClaim ? 'Filing Claim & Notifying Driver...' : 'File Claim & Generate Secret OTP'"></span>
            </button>
        </div>
    </div>
</div>
