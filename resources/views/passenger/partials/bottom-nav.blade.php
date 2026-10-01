<!-- Fixed Bottom Navigation Bar (Visible on Mobile & Tablet, Hidden on Desktop/Laptop) -->
<!-- 5 Items: Home | SOS | Camera (Scan) | Lost Items | Rides -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 z-30 bg-slate-900/95 backdrop-blur-lg border-t border-slate-800 px-3 py-2 flex items-center justify-around shadow-2xl safe-area-pb">

    <!-- 1. Home -->
    <button @click="activeTab = 'home'"
            class="flex-1 flex flex-col items-center justify-center py-1 transition text-xs font-medium"
            :class="activeTab === 'home' ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-slate-300'">
        <i class="fa-solid fa-house text-lg mb-1"></i>
        <span class="text-[10px]">Home</span>
    </button>

    <!-- 2. SOS (Emergency) -->
    <button @click="sosDrawer = true"
            class="flex-1 flex flex-col items-center justify-center py-1 transition text-xs font-medium text-rose-400 hover:text-rose-300 active:scale-95">
        <i class="fa-solid fa-circle-exclamation text-lg mb-1 animate-pulse"></i>
        <span class="text-[10px] font-bold">SOS</span>
    </button>

    <!-- 3. Camera (Elevated Center QR Scanner) -->
    <div class="flex-1 flex justify-center -mt-6">
        <button @click="openQrScanner()"
                class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white flex flex-col items-center justify-center shadow-lg shadow-indigo-600/40 active:scale-90 transition border-4 border-slate-950"
                title="Scan Driver QR">
            <i class="fa-solid fa-camera text-xl"></i>
        </button>
    </div>

    <!-- 4. Lost Items (Claims) -->
    <button @click="activeTab = 'claims'"
            class="flex-1 flex flex-col items-center justify-center py-1 transition text-xs font-medium relative"
            :class="activeTab === 'claims' ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-slate-300'">
        <i class="fa-solid fa-box-open text-lg mb-1"></i>
        <span class="text-[10px]">Lost Items</span>
        <template x-if="claimsList.length > 0">
            <span class="absolute top-1 right-3 w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
        </template>
    </button>

    <!-- 5. Rides (History) -->
    <button @click="activeTab = 'rides'"
            class="flex-1 flex flex-col items-center justify-center py-1 transition text-xs font-medium"
            :class="activeTab === 'rides' ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-slate-300'">
        <i class="fa-solid fa-route text-lg mb-1"></i>
        <span class="text-[10px]">Rides</span>
    </button>

</nav>
