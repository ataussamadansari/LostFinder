<!-- 6. Emergency Assistance SOS Drawer / Modal -->
<div x-show="sosDrawer"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-950/85 backdrop-blur-md"
     x-transition>
    <div @click.away="sosDrawer = false"
         class="w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl bg-slate-900 border border-rose-500/40 p-6 space-y-5 shadow-2xl">

        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <div class="flex items-center space-x-2.5 text-rose-400 font-bold text-base sm:text-lg">
                <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
                <span>Emergency SOS Help</span>
            </div>
            <button @click="sosDrawer = false" class="text-slate-400 hover:text-white p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <p class="text-xs sm:text-sm text-slate-300">
            Immediate assistance is available 24x7 across all Varanasi Ghats, transit points, and railway stations:
        </p>

        <!-- Direct Dial Buttons -->
        <div class="space-y-3">
            <a :href="`tel:${emergencyContacts.police}`"
               class="p-4 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs sm:text-sm flex items-center justify-between shadow-lg shadow-rose-900/40 transition">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-shield text-xl"></i>
                    <div>
                        <p class="text-sm font-extrabold">Uttar Pradesh Police</p>
                        <p class="text-[11px] text-rose-100 font-normal">Immediate Field Response</p>
                    </div>
                </div>
                <span class="font-mono text-base font-black px-3 py-1 rounded-lg bg-rose-700" x-text="emergencyContacts.police"></span>
            </a>

            <a :href="`tel:${emergencyContacts.tourist}`"
               class="p-4 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs sm:text-sm flex items-center justify-between shadow-lg shadow-indigo-900/40 transition">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-headset text-xl"></i>
                    <div>
                        <p class="text-sm font-extrabold">National Tourist Helpline</p>
                        <p class="text-[11px] text-indigo-100 font-normal">Multi-Language Assistance</p>
                    </div>
                </div>
                <span class="font-mono text-base font-black px-3 py-1 rounded-lg bg-indigo-700" x-text="emergencyContacts.tourist"></span>
            </a>

            <a :href="`tel:${emergencyContacts.support}`"
               class="p-4 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs sm:text-sm flex items-center justify-between border border-slate-700 transition">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-phone-volume text-lg text-emerald-400"></i>
                    <div>
                        <p class="text-xs sm:text-sm font-bold">LostFinder Support Desk</p>
                        <p class="text-[11px] text-slate-400 font-normal">Transit Claims Assistance</p>
                    </div>
                </div>
                <span class="font-mono text-xs px-2.5 py-1 rounded-lg bg-slate-900 text-slate-300" x-text="emergencyContacts.support"></span>
            </a>
        </div>

        <button @click="sosDrawer = false"
                class="w-full py-3 rounded-xl bg-slate-800 text-slate-400 hover:text-white text-xs font-semibold transition">
            Close SOS Assistance
        </button>
    </div>
</div>
