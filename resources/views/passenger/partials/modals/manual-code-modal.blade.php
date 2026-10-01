<!-- 2. Manual Token Entry Modal -->
<div x-show="manualCodeModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
     x-transition>
    <div @click.away="manualCodeModal = false"
         class="w-full max-w-sm sm:max-w-md rounded-3xl bg-slate-900 border border-slate-800 p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-sm sm:text-base font-bold text-white flex items-center space-x-2">
                <i class="fa-solid fa-keyboard text-indigo-400"></i>
                <span>Enter QR Token Manually</span>
            </h3>
            <button @click="manualCodeModal = false" class="text-slate-400 hover:text-white p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-300">Driver Token or Sticker Code</label>
            <input type="text"
                   x-model="manualToken"
                   placeholder="e.g. DRV_DEMO123456 or full QR URL"
                   class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 font-mono">
        </div>

        <button @click="processManualToken()"
                :disabled="!manualToken.trim() || fetchingDriverInfo"
                class="w-full py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/30 transition flex items-center justify-center space-x-2">
            <i class="fa-solid fa-magnifying-glass text-xs"></i>
            <span x-text="fetchingDriverInfo ? 'Verifying...' : 'Look Up Driver Details'"></span>
        </button>
    </div>
</div>
