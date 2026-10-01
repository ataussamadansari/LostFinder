<!-- Top Navigation Header (Desktop, Tablet & Mobile) -->
<header class="sticky top-0 z-30 bg-slate-900/90 backdrop-blur-md border-b border-slate-800/80 px-4 sm:px-6 lg:px-8 py-3.5">
    <div class="max-w-7xl mx-auto flex items-center justify-between">

        <!-- Logo & City Tagline -->
        <div class="flex items-center space-x-3 cursor-pointer" @click="activeTab = 'home'">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center shadow-lg shadow-indigo-500/25">
                <i class="fa-solid fa-shield-cat text-white text-lg"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-lg font-extrabold tracking-tight bg-gradient-to-r from-white via-indigo-100 to-indigo-300 bg-clip-text text-transparent">
                        LostFinder
                    </h1>
                    <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                        Tourist PWA
                    </span>
                </div>
                <p class="text-xs font-medium text-slate-400 flex items-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block mr-1.5 animate-pulse"></span>
                    Varanasi Safe Smart Transit
                </p>
            </div>
        </div>

        <!-- Desktop / Tablet Navigation Links (Hidden on Mobile) -->
        <nav class="hidden md:flex items-center space-x-1 lg:space-x-2 bg-slate-900 border border-slate-800/80 p-1 rounded-xl">
            <button @click="activeTab = 'home'"
                    class="px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center space-x-2"
                    :class="activeTab === 'home' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'">
                <i class="fa-solid fa-house"></i>
                <span>Scan & Home</span>
            </button>
            <button @click="activeTab = 'claims'"
                    class="px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center space-x-2 relative"
                    :class="activeTab === 'claims' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'">
                <i class="fa-solid fa-box-open"></i>
                <span>Lost Items</span>
                <template x-if="claimsList.length > 0">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                </template>
            </button>
            <button @click="activeTab = 'rides'"
                    class="px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center space-x-2"
                    :class="activeTab === 'rides' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'">
                <i class="fa-solid fa-route"></i>
                <span>Ride History</span>
            </button>
        </nav>

        <!-- Header Actions: SOS & Auth -->
        <div class="flex items-center space-x-2.5 sm:space-x-3">
            <!-- SOS Button -->
            <button @click="sosDrawer = true"
                    class="px-3 py-2 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400 font-bold text-xs sm:text-sm flex items-center space-x-1.5 hover:bg-rose-500/25 active:scale-95 transition shadow-lg shadow-rose-950/30">
                <i class="fa-solid fa-circle-exclamation text-rose-400 animate-pulse"></i>
                <span>SOS Emergency</span>
            </button>

            <!-- User Auth Info / Login -->
            <template x-if="auth.token">
                <div class="flex items-center space-x-2">
                    <div class="hidden sm:flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700/80 text-xs">
                        <i class="fa-solid fa-user-circle text-indigo-400 text-sm"></i>
                        <span class="font-medium text-slate-300" x-text="auth.user?.name || auth.user?.phone || 'Tourist'"></span>
                    </div>
                    <button @click="logout()"
                            class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700/80 text-slate-300 flex items-center justify-center hover:bg-rose-500/20 hover:text-rose-400 hover:border-rose-500/40 text-xs transition"
                            title="Sign Out">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </button>
                </div>
            </template>

            <template x-if="!auth.token">
                <button @click="loginModal = true"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs sm:text-sm active:scale-95 shadow-lg shadow-indigo-600/30 transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-right-to-bracket text-xs"></i>
                    <span>Sign In</span>
                </button>
            </template>
        </div>

    </div>
</header>
