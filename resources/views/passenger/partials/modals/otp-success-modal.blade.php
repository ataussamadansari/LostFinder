<!-- 5. Claim Success & Secret OTP Modal -->
<div x-show="otpSuccessModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md"
     x-transition>
    <div class="w-full max-w-sm sm:max-w-md rounded-3xl bg-slate-900 border border-indigo-500/50 p-6 sm:p-8 space-y-5 shadow-2xl text-center relative overflow-hidden">
        <div class="w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center mx-auto text-2xl animate-bounce">
            <i class="fa-solid fa-shield-check"></i>
        </div>

        <div class="space-y-1">
            <h3 class="text-lg font-bold text-white">Claim Filed Successfully!</h3>
            <p class="text-xs text-slate-300">
                Driver has been notified. Keep this secret Handover OTP safe.
            </p>
        </div>

        <!-- Big Secret OTP Box -->
        <div class="p-5 rounded-2xl bg-indigo-950/60 border-2 border-indigo-500/50 space-y-2">
            <span class="text-[11px] font-bold text-indigo-300 uppercase tracking-wider">Your Handover OTP</span>
            <div class="text-3xl sm:text-4xl font-mono font-black tracking-widest text-white select-all"
                 x-text="generatedOtp"></div>
            <p class="text-[11px] text-slate-400">
                Only tell this number to the driver when they hand you your item.
            </p>
        </div>

        <button @click="otpSuccessModal = false; activeTab = 'claims'"
                class="w-full py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs sm:text-sm shadow-lg shadow-indigo-600/30 transition">
            View in My Lost Items
        </button>
    </div>
</div>
