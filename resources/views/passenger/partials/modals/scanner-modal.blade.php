<!-- 1. HTML5 In-Browser Camera QR Scanner Modal -->
<div x-show="scannerModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
     x-transition>
    <div @click.away="closeQrScanner()"
         class="w-full max-w-sm sm:max-w-md rounded-3xl bg-slate-900 border border-slate-800 p-6 space-y-4 shadow-2xl relative text-center">

        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-sm sm:text-base font-bold text-white flex items-center space-x-2">
                <i class="fa-solid fa-camera text-indigo-400"></i>
                <span>Scan Driver QR Code</span>
            </h3>
            <button @click="closeQrScanner()" class="text-slate-400 hover:text-white p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Video Viewport -->
        <div class="relative w-full aspect-square rounded-2xl bg-black overflow-hidden border-2 border-dashed border-indigo-500/50 flex items-center justify-center">
            <div id="qr-reader" class="w-full h-full"></div>
            <template x-if="scannerLoading">
                <div class="absolute inset-0 flex flex-col items-center justify-center bg-black/80 space-y-2">
                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-indigo-400"></i>
                    <span class="text-xs text-slate-300">Initializing Camera...</span>
                </div>
            </template>
        </div>

        <p class="text-xs text-slate-400">
            Point camera at the QR sticker inside the vehicle.
        </p>

        <button @click="closeQrScanner(); manualCodeModal = true"
                class="w-full py-3 rounded-xl bg-slate-800 text-slate-300 text-xs sm:text-sm font-semibold hover:bg-slate-700 transition">
            Switch to Manual Token Entry
        </button>
    </div>
</div>
