<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>LostFinder - Smart Tourist & Transit Security</title>

    <!-- PWA Settings -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#1e1b4b',
                        }
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- QR Code Scanner Library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #0f172a;
        }
        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 9999px;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased selection:bg-indigo-500 selection:text-white"
      x-data="lostFinderApp()"
      x-init="initApp()"
      x-cloak>

    <!-- Responsive App Container: Phone (100%), Tablet/Laptop/Desktop (Centered max-w-7xl with rich multi-column layout) -->
    <div class="min-h-screen bg-slate-950 flex flex-col relative pb-20 lg:pb-8">

        <!-- Top Navigation Header (Responsive across Desktop, Tablet & Mobile) -->
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

        <!-- Main Content Area: Responsive Multi-Column Layout -->
        <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-5">

            <!-- Promotions Banner (Visible on all devices) -->
            <template x-if="promotions && promotions.length > 0">
                <div class="mb-6 overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-r from-indigo-950/70 via-slate-900 to-indigo-950/50 shadow-xl">
                    <div class="p-4 sm:p-5 flex items-center justify-between">
                        <div class="space-y-1.5 pr-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-bold bg-indigo-500/20 text-indigo-300 uppercase tracking-wider border border-indigo-500/30">
                                <i class="fa-solid fa-bolt mr-1.5 text-amber-400"></i> Tourist Safe Transit Alert
                            </span>
                            <h3 class="text-base sm:text-lg font-bold text-white line-clamp-1" x-text="promotions[currentBannerIndex]?.title"></h3>
                            <p class="text-xs sm:text-sm text-slate-300 line-clamp-2" x-text="promotions[currentBannerIndex]?.description"></p>
                            <template x-if="promotions[currentBannerIndex]?.cta_url">
                                <a :href="promotions[currentBannerIndex]?.cta_url"
                                   @click="recordBannerClick(promotions[currentBannerIndex]?.id)"
                                   target="_blank"
                                   class="inline-flex items-center text-xs sm:text-sm font-semibold text-indigo-400 hover:text-indigo-300 pt-1">
                                    <span x-text="promotions[currentBannerIndex]?.cta_label || 'Learn More'"></span>
                                    <i class="fa-solid fa-arrow-right text-xs ml-1.5"></i>
                                </a>
                            </template>
                        </div>
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center shrink-0 text-indigo-400 text-2xl">
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                    </div>
                </div>
            </template>

            <!-- TAB 1: HOME & SCANNER (Responsive Grid on Tablet & Desktop) -->
            <div x-show="activeTab === 'home'">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Left / Main Column (Scan & Active Ride) -->
                    <div class="lg:col-span-7 space-y-6">

                        <!-- Active Ride Ongoing Card (If user currently has an active ride) -->
                        <template x-if="activeRide">
                            <div class="rounded-3xl border border-amber-500/40 bg-gradient-to-br from-amber-950/40 via-slate-900 to-slate-900 p-5 sm:p-6 shadow-2xl shadow-amber-950/30 relative overflow-hidden">
                                <div class="absolute -right-6 -top-6 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

                                <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                                    <div class="flex items-center space-x-2">
                                        <span class="relative flex h-3 w-3">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                        </span>
                                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-emerald-400">Ride in Progress</span>
                                    </div>
                                    <span class="text-xs text-slate-400 font-mono" x-text="rideDuration"></span>
                                </div>

                                <div class="py-4 flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center text-amber-400 text-2xl shadow-inner">
                                            <i class="fa-solid fa-taxi"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-base sm:text-lg font-bold text-white font-mono" x-text="activeRide.vehicle_number"></h4>
                                            <p class="text-xs sm:text-sm text-slate-400 flex items-center space-x-2">
                                                <span x-text="activeRide.vehicle_type || 'Auto / Cab'"></span>
                                                <span>•</span>
                                                <span x-text="activeRide.driver_name || 'Driver'"></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons on Active Ride -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                    <button @click="openClaimModal(activeRide.ride_session_id || activeRide.id)"
                                            class="w-full py-3 px-4 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 hover:bg-rose-500/30 text-xs sm:text-sm font-semibold flex items-center justify-center space-x-2 transition">
                                        <i class="fa-solid fa-box-open text-base"></i>
                                        <span>I Lost An Item</span>
                                    </button>
                                    <button @click="completeRide(activeRide.ride_session_id || activeRide.id)"
                                            :disabled="completingRide"
                                            class="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs sm:text-sm font-semibold flex items-center justify-center space-x-2 shadow-lg shadow-emerald-600/30 transition">
                                        <i class="fa-solid fa-circle-check text-base"></i>
                                        <span x-text="completingRide ? 'Completing...' : 'Complete Ride'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- Primary Card: QR Scanner Trigger -->
                        <div class="rounded-3xl border border-indigo-500/30 bg-gradient-to-b from-indigo-950/40 via-slate-900 to-slate-900/90 p-6 sm:p-8 shadow-2xl relative overflow-hidden text-center">
                            <div class="absolute -top-16 -left-16 w-52 h-52 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>

                            <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 mx-auto flex items-center justify-center shadow-xl shadow-indigo-600/30 mb-4">
                                <i class="fa-solid fa-qrcode text-3xl text-white"></i>
                            </div>

                            <h2 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">Boarding an Auto, Taxi or Cab?</h2>
                            <p class="text-xs sm:text-sm text-slate-300 mt-2 max-w-md mx-auto leading-relaxed">
                                Scan the driver's QR code before departure. Your ride session and GPS location are logged safely. If you leave your belongings behind, our 6-Digit Handover OTP ensures seamless recovery!
                            </p>

                            <!-- Scan Actions -->
                            <div class="mt-6 max-w-md mx-auto space-y-3">
                                <button @click="openQrScanner()"
                                        class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm sm:text-base shadow-xl shadow-indigo-600/30 flex items-center justify-center space-x-2.5 active:scale-98 transition">
                                    <i class="fa-solid fa-camera text-lg"></i>
                                    <span>Scan Driver QR Code Sticker</span>
                                </button>

                                <button @click="manualCodeModal = true"
                                        class="w-full py-3 px-4 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 text-xs sm:text-sm font-semibold border border-slate-700/80 transition flex items-center justify-center space-x-2">
                                    <i class="fa-solid fa-keyboard text-slate-400"></i>
                                    <span>Enter Vehicle / Token Manually</span>
                                </button>
                            </div>

                            <!-- Safety Guarantee Tags -->
                            <div class="mt-6 pt-6 border-t border-slate-800/80 grid grid-cols-3 gap-2 text-xs text-slate-400">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                                    <span class="font-medium">KYC Verified</span>
                                </div>
                                <div class="flex items-center justify-center space-x-1.5">
                                    <i class="fa-solid fa-location-dot text-indigo-400"></i>
                                    <span class="font-medium">GPS Logged</span>
                                </div>
                                <div class="flex items-center justify-center space-x-1.5">
                                    <i class="fa-solid fa-key text-amber-400"></i>
                                    <span class="font-medium">Handover OTP</span>
                                </div>
                            </div>
                        </div>

                        <!-- Emergency SOS Quick Card -->
                        <div class="rounded-2xl border border-rose-500/30 bg-rose-950/20 p-4 sm:p-5 flex items-center justify-between shadow-lg">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-400 text-xl shrink-0">
                                    <i class="fa-solid fa-phone-volume"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-rose-300">Need Immediate Emergency Assistance?</h4>
                                    <p class="text-xs text-slate-400">Direct 1-tap call to Varanasi Police (112) & Tourist Care (1363)</p>
                                </div>
                            </div>
                            <button @click="sosDrawer = true"
                                    class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-rose-600/30 transition shrink-0 ml-2">
                                Open SOS
                            </button>
                        </div>

                    </div>

                    <!-- Right Column (Desktop / Tablet Companion: How It Works & Quick Feeds) -->
                    <div class="lg:col-span-5 space-y-6">

                        <!-- How It Works Step Guide -->
                        <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-5 sm:p-6 space-y-4 shadow-xl">
                            <h3 class="text-xs font-bold text-indigo-300 uppercase tracking-wider flex items-center space-x-2">
                                <i class="fa-solid fa-circle-question text-indigo-400"></i>
                                <span>3-Step Safety Workflow</span>
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-start space-x-3.5 text-xs sm:text-sm">
                                    <div class="w-7 h-7 rounded-xl bg-indigo-600/30 text-indigo-300 font-bold flex items-center justify-center shrink-0 mt-0.5 border border-indigo-500/40">1</div>
                                    <div>
                                        <p class="font-bold text-white">Scan Vehicle QR Code</p>
                                        <p class="text-slate-400 text-xs">Scan the sticker placed inside the auto or cab before departure.</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3.5 text-xs sm:text-sm">
                                    <div class="w-7 h-7 rounded-xl bg-indigo-600/30 text-indigo-300 font-bold flex items-center justify-center shrink-0 mt-0.5 border border-indigo-500/40">2</div>
                                    <div>
                                        <p class="font-bold text-white">Instant Lost Item Reporting</p>
                                        <p class="text-slate-400 text-xs">Forgot a bag or phone? Report in 1 click with category and description.</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3.5 text-xs sm:text-sm">
                                    <div class="w-7 h-7 rounded-xl bg-indigo-600/30 text-indigo-300 font-bold flex items-center justify-center shrink-0 mt-0.5 border border-indigo-500/40">3</div>
                                    <div>
                                        <p class="font-bold text-white">Handover with Secret 6-Digit OTP</p>
                                        <p class="text-slate-400 text-xs">The driver returns your item. You give the OTP only after receiving it.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Claims Preview (If user has claims) -->
                        <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-5 sm:p-6 space-y-4 shadow-xl">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-bold text-indigo-300 uppercase tracking-wider flex items-center space-x-2">
                                    <i class="fa-solid fa-box-open text-indigo-400"></i>
                                    <span>Active Claims & OTP</span>
                                </h3>
                                <button @click="activeTab = 'claims'" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold">
                                    View All
                                </button>
                            </div>

                            <template x-if="!auth.token">
                                <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 text-center space-y-2">
                                    <p class="text-xs text-slate-400">Sign in to track your lost belongings and access your Secret OTPs.</p>
                                    <button @click="loginModal = true" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium">
                                        Sign In
                                    </button>
                                </div>
                            </template>

                            <template x-if="auth.token && claimsList.length === 0">
                                <p class="text-xs text-slate-400 py-3 text-center">No active lost item claims. You're all good!</p>
                            </template>

                            <template x-if="auth.token && claimsList.length > 0">
                                <div class="space-y-3">
                                    <template x-for="claim in claimsList.slice(0, 2)" :key="claim.id">
                                        <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-bold text-white" x-text="claim.item_category"></span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                                      :class="{
                                                          'bg-amber-500/20 text-amber-400': claim.claim_status === 'reported',
                                                          'bg-sky-500/20 text-sky-400': claim.claim_status === 'searching',
                                                          'bg-emerald-500/20 text-emerald-400': claim.claim_status === 'found',
                                                          'bg-slate-700/50 text-slate-400': claim.claim_status === 'returned'
                                                      }"
                                                      x-text="claim.claim_status"></span>
                                            </div>
                                            <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-800/80 font-mono">
                                                <span class="text-slate-400">Handover OTP:</span>
                                                <span class="text-amber-400 font-extrabold tracking-wider" x-text="claim.handover_otp"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                    </div>

                </div>
            </div>

            <!-- TAB 2: MY CLAIMS TAB (Full Responsive Grid) -->
            <div x-show="activeTab === 'claims'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold text-white">My Lost Item Claims</h2>
                        <p class="text-xs sm:text-sm text-slate-400">Track claim statuses and share your secret OTP only at physical handover.</p>
                    </div>
                    <button @click="fetchTouristClaims()"
                            class="px-3 py-1.5 rounded-xl bg-slate-800 border border-slate-700 text-xs text-indigo-400 hover:text-indigo-300 flex items-center space-x-1.5 transition">
                        <i class="fa-solid fa-arrows-rotate" :class="{ 'fa-spin': loadingClaims }"></i>
                        <span>Refresh</span>
                    </button>
                </div>

                <template x-if="!auth.token">
                    <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-8 text-center space-y-3 max-w-md mx-auto">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center mx-auto text-2xl">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <h4 class="text-base font-bold text-white">Sign In to View Claims</h4>
                        <p class="text-xs text-slate-400">Track items you have reported lost and access your secure Handover OTPs.</p>
                        <button @click="loginModal = true"
                                class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/30">
                            Sign In / Quick Demo
                        </button>
                    </div>
                </template>

                <template x-if="auth.token && claimsList.length === 0 && !loadingClaims">
                    <div class="rounded-3xl border border-slate-800 bg-slate-900/40 p-12 text-center space-y-2 max-w-md mx-auto">
                        <div class="w-14 h-14 rounded-full bg-slate-800 text-slate-500 flex items-center justify-center mx-auto text-2xl">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                        <h4 class="text-base font-semibold text-slate-300">No Lost Items Reported</h4>
                        <p class="text-xs text-slate-500">All safe! Any lost items you report will appear here along with secret recovery OTPs.</p>
                    </div>
                </template>

                <!-- Claims Grid (1 col on mobile, 2 cols on tablet, 3 cols on desktop) -->
                <template x-if="auth.token">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <template x-for="claim in claimsList" :key="claim.id">
                            <div class="rounded-3xl border border-slate-800 bg-slate-900 p-5 space-y-4 shadow-xl relative overflow-hidden flex flex-col justify-between">

                                <div class="space-y-3">
                                    <!-- Status & Date -->
                                    <div class="flex items-center justify-between">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider flex items-center space-x-1.5"
                                              :class="{
                                                  'bg-amber-500/20 text-amber-400 border border-amber-500/30': claim.claim_status === 'reported',
                                                  'bg-sky-500/20 text-sky-400 border border-sky-500/30': claim.claim_status === 'searching',
                                                  'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 animate-pulse': claim.claim_status === 'found',
                                                  'bg-slate-700/50 text-slate-400': claim.claim_status === 'returned'
                                              }">
                                            <i class="fa-solid" :class="{
                                                'fa-bell': claim.claim_status === 'reported',
                                                'fa-magnifying-glass': claim.claim_status === 'searching',
                                                'fa-circle-check': claim.claim_status === 'found' || claim.claim_status === 'returned'
                                            }"></i>
                                            <span x-text="claim.claim_status"></span>
                                        </span>
                                        <span class="text-xs text-slate-400 font-mono" x-text="formatDate(claim.created_at)"></span>
                                    </div>

                                    <!-- Category & Bounty -->
                                    <div class="space-y-1">
                                        <div class="flex items-center space-x-2">
                                            <h4 class="text-base font-bold text-white" x-text="claim.item_category"></h4>
                                            <template x-if="claim.bounty_amount > 0">
                                                <span class="text-[11px] font-bold bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded border border-amber-500/30">
                                                    ₹<span x-text="claim.bounty_amount"></span> Reward
                                                </span>
                                            </template>
                                        </div>
                                        <p class="text-xs text-slate-300 leading-relaxed" x-text="claim.item_description"></p>
                                    </div>

                                    <!-- Vehicle / Driver Info -->
                                    <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-xs flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <i class="fa-solid fa-taxi text-slate-400"></i>
                                            <span class="text-slate-200 font-bold font-mono" x-text="claim.ride_session?.driver_profile?.vehicle_number || 'Vehicle Assigned'"></span>
                                        </div>
                                        <span class="text-slate-400" x-text="claim.ride_session?.driver_profile?.user?.name || 'Driver'"></span>
                                    </div>
                                </div>

                                <!-- Bottom Section: Handover OTP / Resolution -->
                                <div class="pt-3 space-y-3 border-t border-slate-800/80">
                                    <template x-if="claim.claim_status !== 'returned'">
                                        <div class="p-4 rounded-2xl bg-indigo-950/40 border border-indigo-500/40 text-center space-y-1">
                                            <div class="text-[11px] font-bold text-indigo-300 uppercase tracking-wider">
                                                Secret Handover OTP
                                            </div>
                                            <div class="text-3xl font-extrabold tracking-widest text-amber-300 font-mono"
                                                 x-text="claim.handover_otp">
                                            </div>
                                            <p class="text-[10px] text-indigo-300/80 leading-tight">
                                                Share ONLY when item is physically in your hand.
                                            </p>
                                        </div>
                                    </template>

                                    <template x-if="claim.claim_status === 'returned'">
                                        <div class="p-3 rounded-2xl bg-emerald-950/30 border border-emerald-500/30 text-center text-xs text-emerald-300 font-semibold flex items-center justify-center space-x-2">
                                            <i class="fa-solid fa-circle-check text-emerald-400"></i>
                                            <span>Item Successfully Returned</span>
                                        </div>
                                    </template>

                                    <template x-if="claim.claim_status === 'reported' || claim.claim_status === 'searching'">
                                        <div class="flex justify-end">
                                            <button @click="cancelClaim(claim.id)"
                                                    class="text-xs text-rose-400 hover:text-rose-300 font-semibold">
                                                Cancel Claim
                                            </button>
                                        </div>
                                    </template>
                                </div>

                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- TAB 3: RIDES HISTORY TAB (Full Responsive Grid) -->
            <div x-show="activeTab === 'rides'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold text-white">Ride History</h2>
                        <p class="text-xs sm:text-sm text-slate-400">History of your scanned autos, taxis, and transit logs.</p>
                    </div>
                    <button @click="fetchTouristRides()"
                            class="px-3 py-1.5 rounded-xl bg-slate-800 border border-slate-700 text-xs text-indigo-400 hover:text-indigo-300 flex items-center space-x-1.5 transition">
                        <i class="fa-solid fa-arrows-rotate" :class="{ 'fa-spin': loadingRides }"></i>
                        <span>Refresh</span>
                    </button>
                </div>

                <template x-if="!auth.token">
                    <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-8 text-center space-y-3 max-w-md mx-auto">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center mx-auto text-2xl">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <h4 class="text-base font-bold text-white">Sign In to View Ride History</h4>
                        <p class="text-xs text-slate-400">See your scanned rides, vehicle logs, and past journey details.</p>
                        <button @click="loginModal = true"
                                class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/30">
                            Sign In / Quick Demo
                        </button>
                    </div>
                </template>

                <template x-if="auth.token && ridesList.length === 0 && !loadingRides">
                    <div class="rounded-3xl border border-slate-800 bg-slate-900/40 p-12 text-center space-y-2 max-w-md mx-auto">
                        <div class="w-14 h-14 rounded-full bg-slate-800 text-slate-500 flex items-center justify-center mx-auto text-2xl">
                            <i class="fa-solid fa-route"></i>
                        </div>
                        <h4 class="text-base font-semibold text-slate-300">No Rides Recorded Yet</h4>
                        <p class="text-xs text-slate-500">Scan any registered auto or cab QR code to log your safe transit.</p>
                    </div>
                </template>

                <!-- Rides Grid (1 col on mobile, 2 cols on tablet, 3 cols on desktop) -->
                <template x-if="auth.token">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <template x-for="ride in ridesList" :key="ride.id">
                            <div class="rounded-3xl border border-slate-800 bg-slate-900 p-5 space-y-4 shadow-xl">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center text-amber-400 text-lg">
                                            <i class="fa-solid fa-taxi"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm sm:text-base font-bold text-white font-mono" x-text="ride.driver_profile?.vehicle_number || 'Vehicle'"></h4>
                                            <p class="text-xs text-slate-400" x-text="ride.driver_profile?.vehicle_type || 'Auto'"></p>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
                                          :class="{
                                              'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': ride.status === 'active',
                                              'bg-slate-700/50 text-slate-400': ride.status === 'completed',
                                              'bg-rose-500/20 text-rose-400 border border-rose-500/30': ride.status === 'flagged'
                                          }"
                                          x-text="ride.status">
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-xs text-slate-400 pt-3 border-t border-slate-800/80">
                                    <span class="font-mono" x-text="formatDate(ride.created_at)"></span>
                                    <button @click="openClaimModal(ride.id)"
                                            class="text-rose-400 hover:text-rose-300 font-semibold flex items-center space-x-1.5">
                                        <i class="fa-solid fa-box-open"></i>
                                        <span>Report Lost Item</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

        </main>

        <!-- Fixed Bottom Navigation Bar (Visible on Mobile & Tablet, Hidden on Desktop/Laptop) -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-30 bg-slate-900/95 backdrop-blur-lg border-t border-slate-800 px-6 py-2.5 flex items-center justify-around shadow-2xl">
            <!-- Tab: Home -->
            <button @click="activeTab = 'home'"
                    class="flex flex-col items-center space-y-1 transition text-xs font-medium"
                    :class="activeTab === 'home' ? 'text-indigo-400' : 'text-slate-400 hover:text-slate-300'">
                <i class="fa-solid fa-house text-lg"></i>
                <span class="text-[11px]">Home</span>
            </button>

            <!-- Center Floating Scan Action -->
            <button @click="openQrScanner()"
                    class="-mt-6 w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white flex items-center justify-center text-xl shadow-lg shadow-indigo-600/40 active:scale-95 transition border-4 border-slate-900">
                <i class="fa-solid fa-camera"></i>
            </button>

            <!-- Tab: Claims -->
            <button @click="activeTab = 'claims'"
                    class="flex flex-col items-center space-y-1 transition text-xs font-medium relative"
                    :class="activeTab === 'claims' ? 'text-indigo-400' : 'text-slate-400 hover:text-slate-300'">
                <i class="fa-solid fa-box-open text-lg"></i>
                <span class="text-[11px]">Lost Items</span>
                <template x-if="claimsList.length > 0">
                    <span class="absolute top-0 right-3 w-2 h-2 rounded-full bg-rose-500"></span>
                </template>
            </button>

            <!-- Tab: Rides -->
            <button @click="activeTab = 'rides'"
                    class="flex flex-col items-center space-y-1 transition text-xs font-medium"
                    :class="activeTab === 'rides' ? 'text-indigo-400' : 'text-slate-400 hover:text-slate-300'">
                <i class="fa-solid fa-route text-lg"></i>
                <span class="text-[11px]">Rides</span>
            </button>
        </nav>

        <!-- ================= MODALS & DRAWERS (Centered & Responsive) ================= -->

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

        <!-- 2. Manual Token Entry Modal -->
        <div x-show="manualCodeModal"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
             x-transition>
            <div @click.away="manualCodeModal = false"
                 class="w-full max-w-sm sm:max-w-md rounded-3xl bg-slate-900 border border-slate-800 p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-sm sm:text-base font-bold text-white">Enter QR Token Manually</h3>
                    <button @click="manualCodeModal = false" class="text-slate-400 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold text-slate-300">Driver Token or Sticker Code</label>
                    <input type="text"
                           x-model="manualToken"
                           placeholder="e.g. qr_driver_test_9876543210 or full QR URL"
                           class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 font-mono">
                </div>

                <button @click="processManualToken()"
                        :disabled="!manualToken.trim() || fetchingDriverInfo"
                        class="w-full py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/30 transition flex items-center justify-center space-x-2">
                    <span x-text="fetchingDriverInfo ? 'Verifying...' : 'Look Up Driver Details'"></span>
                </button>
            </div>
        </div>

        <!-- 3. Driver Verification & Start Ride Modal -->
        <div x-show="driverVerificationModal"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
             x-transition>
            <div class="w-full max-w-sm sm:max-w-md rounded-3xl bg-slate-900 border border-slate-800 p-6 space-y-5 shadow-2xl relative overflow-hidden">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <h3 class="text-sm sm:text-base font-bold text-white">Driver Verification</h3>
                    </div>
                    <button @click="driverVerificationModal = false" class="text-slate-400 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Driver Details Card -->
                <template x-if="scannedDriver">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-4 p-4 rounded-2xl bg-slate-950 border border-slate-800">
                            <div class="w-16 h-16 rounded-2xl bg-slate-800 border border-indigo-500/30 overflow-hidden flex items-center justify-center text-slate-400 shrink-0">
                                <template x-if="scannedDriver.profile_picture_url">
                                    <img :src="scannedDriver.profile_picture_url" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!scannedDriver.profile_picture_url">
                                    <i class="fa-solid fa-user-tie text-2xl text-slate-500"></i>
                                </template>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center space-x-1.5">
                                    <h4 class="text-base font-bold text-white" x-text="scannedDriver.driver_name"></h4>
                                    <i class="fa-solid fa-circle-check text-emerald-400 text-xs" title="KYC Verified"></i>
                                </div>
                                <p class="text-sm font-mono font-bold text-amber-400" x-text="scannedDriver.vehicle_number"></p>
                                <p class="text-xs text-slate-400 flex items-center space-x-2">
                                    <span x-text="scannedDriver.vehicle_type || 'Auto / Cab'"></span>
                                    <span>•</span>
                                    <span><span x-text="scannedDriver.total_trips || 0"></span> safe rides</span>
                                </p>
                            </div>
                        </div>

                        <!-- Safety Badge -->
                        <div class="p-3.5 rounded-xl bg-emerald-950/20 border border-emerald-500/30 flex items-center space-x-3 text-xs sm:text-sm text-emerald-300">
                            <i class="fa-solid fa-shield-halved text-lg text-emerald-400 shrink-0"></i>
                            <span>Verified in City Database. GPS tracking timestamped with this ride.</span>
                        </div>

                        <!-- Start Ride Button -->
                        <button @click="startAndLogRide()"
                                :disabled="startingRide"
                                class="w-full py-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm sm:text-base shadow-xl shadow-emerald-600/30 flex items-center justify-center space-x-2 transition">
                            <i class="fa-solid fa-route"></i>
                            <span x-text="startingRide ? 'Logging Ride with GPS...' : 'Confirm & Start Safe Ride'"></span>
                        </button>
                    </div>
                </template>
            </div>
        </div>

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
                    <button @click="claimModal = false" class="text-slate-400 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
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
                            <span class="text-[11px] text-amber-400">Boosts driver return speed</span>
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
                    <p class="text-xs sm:text-sm text-slate-300">Driver has been notified with high priority alert.</p>
                </div>

                <!-- OTP High-Contrast Box -->
                <div class="p-5 rounded-2xl bg-indigo-950/60 border border-indigo-500/50 space-y-2 shadow-inner">
                    <div class="text-xs font-bold uppercase tracking-wider text-indigo-300">
                        Your Secret Handover OTP
                    </div>
                    <div class="text-4xl sm:text-5xl font-extrabold tracking-widest text-amber-300 font-mono"
                         x-text="generatedOtp">
                    </div>
                    <p class="text-xs text-slate-300">
                        Give this 6-digit code to the driver <strong>ONLY WHEN</strong> your item is securely returned into your hand.
                    </p>
                </div>

                <button @click="otpSuccessModal = false; activeTab = 'claims'"
                        class="w-full py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs sm:text-sm shadow-lg shadow-indigo-600/30 transition">
                    View in My Claims
                </button>
            </div>
        </div>

        <!-- 6. SOS Emergency Drawer / Modal -->
        <div x-show="sosDrawer"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
             x-transition>
            <div @click.away="sosDrawer = false"
                 class="w-full max-w-sm sm:max-w-md rounded-3xl bg-slate-900 border border-slate-800 p-6 space-y-5 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-sm sm:text-base font-bold text-rose-400 flex items-center space-x-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Emergency & Tourist Support</span>
                    </h3>
                    <button @click="sosDrawer = false" class="text-slate-400 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <p class="text-xs sm:text-sm text-slate-300">
                    1-Tap direct dialing for emergency response and tourist assistance in Varanasi.
                </p>

                <div class="space-y-3">
                    <!-- Police 112 -->
                    <a :href="'tel:' + emergencyContacts.police"
                       class="w-full p-4 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs sm:text-sm flex items-center justify-between shadow-lg shadow-rose-600/30 transition">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-shield-halved text-xl"></i>
                            <div>
                                <p class="text-xs sm:text-sm font-bold">Police Emergency</p>
                                <p class="text-[11px] opacity-80">Immediate law enforcement dispatch</p>
                            </div>
                        </div>
                        <span class="text-base font-mono font-extrabold" x-text="emergencyContacts.police"></span>
                    </a>

                    <!-- Tourist Helpline 1363 -->
                    <a :href="'tel:' + emergencyContacts.tourist"
                       class="w-full p-4 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs sm:text-sm flex items-center justify-between shadow-lg shadow-indigo-600/30 transition">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-compass text-xl"></i>
                            <div>
                                <p class="text-xs sm:text-sm font-bold">Tourist Helpline</p>
                                <p class="text-[11px] opacity-80">Multilingual 24/7 tourist care</p>
                            </div>
                        </div>
                        <span class="text-base font-mono font-extrabold" x-text="emergencyContacts.tourist"></span>
                    </a>

                    <!-- Platform Support -->
                    <a :href="'tel:' + emergencyContacts.support"
                       class="w-full p-4 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs sm:text-sm flex items-center justify-between border border-slate-700 transition">
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-headset text-xl text-indigo-400"></i>
                            <div>
                                <p class="text-xs sm:text-sm font-bold">LostFinder Support</p>
                                <p class="text-[11px] text-slate-400">Dispute & Recovery Desk</p>
                            </div>
                        </div>
                        <span class="text-xs sm:text-sm font-mono text-slate-300" x-text="emergencyContacts.support"></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 7. Authentication / Sign In Modal (100% Reliable, Clean OTP Flow) -->
        <div x-show="loginModal"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md"
             x-transition>
            <div @click.away="loginModal = false"
                 class="w-full max-w-sm sm:max-w-md rounded-3xl bg-slate-900 border border-slate-800 p-6 sm:p-8 space-y-5 shadow-2xl relative">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Tourist Sign In</h3>
                    <button @click="loginModal = false" class="text-slate-400 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- 1-Tap Quick Demo Login -->
                <div class="p-4 rounded-2xl bg-indigo-950/40 border border-indigo-500/40 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-bold text-indigo-300">Quick Demo Access</span>
                        <span class="text-[10px] bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded font-mono">1-Tap</span>
                    </div>
                    <p class="text-xs text-slate-300">
                        Instantly sign in with the verified Tourist test account (Alice Johnson).
                    </p>
                    <button @click="quickDemoLogin()"
                            :disabled="loggingIn"
                            class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-indigo-600/30 transition flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-bolt text-amber-300"></i>
                        <span x-text="loggingIn ? 'Signing in...' : 'Sign in as Demo Tourist'"></span>
                    </button>
                </div>

                <div class="relative flex items-center justify-center">
                    <div class="border-t border-slate-800 w-full"></div>
                    <span class="bg-slate-900 px-3 text-xs text-slate-500 uppercase tracking-wider font-semibold">Or with Phone</span>
                </div>

                <!-- Standard Phone + OTP Form -->
                <div class="space-y-3.5">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Mobile Number</label>
                        <input type="tel"
                               x-model="authForm.phone"
                               placeholder="e.g. 9123456789 or +919123456789"
                               class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 font-mono">
                    </div>

                    <template x-if="authForm.otpSent">
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-300">6-Digit OTP</label>
                            <input type="text"
                                   x-model="authForm.otp"
                                   placeholder="123456"
                                   maxlength="6"
                                   class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm sm:text-base placeholder-slate-500 focus:outline-none focus:border-indigo-500 font-mono tracking-widest text-center">
                        </div>
                    </template>

                    <template x-if="!authForm.otpSent">
                        <button @click="sendOtp()"
                                :disabled="!authForm.phone.trim() || sendingOtp"
                                class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs sm:text-sm transition">
                            <span x-text="sendingOtp ? 'Sending OTP...' : 'Send Verification OTP'"></span>
                        </button>
                    </template>

                    <template x-if="authForm.otpSent">
                        <button @click="verifyOtp()"
                                :disabled="!authForm.otp.trim() || loggingIn"
                                class="w-full py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-emerald-600/30 transition">
                            <span x-text="loggingIn ? 'Verifying...' : 'Verify OTP & Continue'"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Floating Toast Notification -->
        <div x-show="toast.show"
             class="fixed top-20 left-1/2 -translate-x-1/2 z-50 px-5 py-2.5 rounded-2xl text-xs sm:text-sm font-semibold shadow-2xl flex items-center space-x-2.5 transition"
             :class="{
                 'bg-emerald-600 text-white': toast.type === 'success',
                 'bg-rose-600 text-white': toast.type === 'error',
                 'bg-indigo-600 text-white': toast.type === 'info'
             }"
             x-transition>
            <i class="fa-solid" :class="{
                'fa-circle-check': toast.type === 'success',
                'fa-circle-xmark': toast.type === 'error',
                'fa-circle-info': toast.type === 'info'
            }"></i>
            <span x-text="toast.message"></span>
        </div>

    </div>

    <!-- Service Worker Registration for PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(err => {
                    console.log('SW registration skipped', err);
                });
            });
        }
    </script>

    <!-- Alpine.js Application State & Logics -->
    <script>
        function lostFinderApp() {
            return {
                activeTab: 'home',
                categories: @json($categories),
                promotions: @json($promotions),
                currentBannerIndex: 0,
                emergencyContacts: {
                    police: '{{ $policeNumber }}',
                    tourist: '{{ $touristHelpline }}',
                    support: '{{ $supportPhone }}'
                },

                // Modals
                scannerModal: false,
                scannerLoading: false,
                html5QrScanner: null,
                manualCodeModal: false,
                manualToken: '',
                driverVerificationModal: false,
                fetchingDriverInfo: false,
                startingRide: false,
                scannedDriver: null,

                activeRide: null,
                rideDuration: 'Just started',
                rideTimerInterval: null,
                completingRide: false,

                claimModal: false,
                claimForm: {
                    rideId: null,
                    category: '',
                    description: '',
                    bounty: ''
                },
                submittingClaim: false,
                otpSuccessModal: false,
                generatedOtp: '',

                claimsList: [],
                loadingClaims: false,

                ridesList: [],
                loadingRides: false,

                sosDrawer: false,

                // Auth
                auth: {
                    token: localStorage.getItem('lf_token') || '',
                    user: JSON.parse(localStorage.getItem('lf_user') || 'null')
                },
                loginModal: false,
                authForm: {
                    phone: '',
                    otp: '',
                    otpSent: false
                },
                sendingOtp: false,
                loggingIn: false,

                toast: {
                    show: false,
                    message: '',
                    type: 'info'
                },

                showToast(message, type = 'info') {
                    this.toast.message = message;
                    this.toast.type = type;
                    this.toast.show = true;
                    setTimeout(() => { this.toast.show = false; }, 3500);
                },

                initApp() {
                    @if(isset($scannedDriver) && $scannedDriver)
                        const preloadedDriver = @json($scannedDriver);
                        this.scannedDriver = {
                            driver_name: preloadedDriver.user?.name || 'Verified Driver',
                            vehicle_number: preloadedDriver.vehicle_number,
                            vehicle_type: preloadedDriver.vehicle_type,
                            profile_picture_url: preloadedDriver.user?.profile_picture_url || null,
                            total_trips: preloadedDriver.total_trips,
                            qr_token: preloadedDriver.qr_code_token
                        };
                        this.driverVerificationModal = true;
                    @endif

                    const savedRide = localStorage.getItem('lf_active_ride');
                    if (savedRide) {
                        try {
                            this.activeRide = JSON.parse(savedRide);
                            this.startRideTimer();
                        } catch (e) {}
                    }

                    if (this.auth.token) {
                        this.fetchTouristClaims();
                        this.fetchTouristRides();
                    }

                    if (this.promotions.length > 1) {
                        setInterval(() => {
                            this.currentBannerIndex = (this.currentBannerIndex + 1) % this.promotions.length;
                        }, 6000);
                    }
                },

                startRideTimer() {
                    if (this.rideTimerInterval) clearInterval(this.rideTimerInterval);
                    this.rideTimerInterval = setInterval(() => {
                        if (!this.activeRide || !this.activeRide.logged_at) return;
                        const start = new Date(this.activeRide.logged_at).getTime();
                        const now = new Date().getTime();
                        const diffMin = Math.max(0, Math.floor((now - start) / 60000));
                        this.rideDuration = diffMin <= 1 ? 'Just started' : `${diffMin} min ago`;
                    }, 30000);
                },

                openQrScanner() {
                    this.scannerModal = true;
                    this.scannerLoading = true;

                    this.$nextTick(() => {
                        try {
                            this.html5QrScanner = new Html5Qrcode("qr-reader");
                            this.html5QrScanner.start(
                                { facingMode: "environment" },
                                { fps: 10, qrbox: { width: 220, height: 220 } },
                                (decodedText) => {
                                    this.closeQrScanner();
                                    this.handleScannedToken(decodedText);
                                },
                                () => {}
                            ).then(() => {
                                this.scannerLoading = false;
                            }).catch(() => {
                                this.scannerLoading = false;
                                this.showToast('Camera permission denied or not available', 'error');
                            });
                        } catch (e) {
                            this.scannerLoading = false;
                            this.showToast('Could not initialize scanner', 'error');
                        }
                    });
                },

                closeQrScanner() {
                    if (this.html5QrScanner) {
                        this.html5QrScanner.stop().catch(() => {}).finally(() => {
                            this.html5QrScanner.clear();
                            this.html5QrScanner = null;
                        });
                    }
                    this.scannerModal = false;
                    this.scannerLoading = false;
                },

                extractToken(input) {
                    if (!input) return '';
                    const match = input.match(/\/ride\/qr\/([a-zA-Z0-9_\-]+)/);
                    if (match && match[1]) {
                        return match[1];
                    }
                    return input.trim();
                },

                processManualToken() {
                    const token = this.extractToken(this.manualToken);
                    if (!token) {
                        this.showToast('Please enter a valid token', 'error');
                        return;
                    }
                    this.manualCodeModal = false;
                    this.handleScannedToken(token);
                },

                async handleScannedToken(rawToken) {
                    const token = this.extractToken(rawToken);
                    this.fetchingDriverInfo = true;

                    try {
                        const res = await fetch(`/api/v1/ride/driver-info/${token}`);
                        const data = await res.json();

                        if (res.ok && data.status) {
                            this.scannedDriver = data.data;
                            this.driverVerificationModal = true;
                        } else {
                            this.showToast(data.message || 'Driver QR not found', 'error');
                        }
                    } catch (e) {
                        this.showToast('Failed to connect to verification server', 'error');
                    } finally {
                        this.fetchingDriverInfo = false;
                    }
                },

                async startAndLogRide() {
                    if (!this.auth.token) {
                        this.driverVerificationModal = false;
                        this.loginModal = true;
                        this.showToast('Please sign in to log your safe ride', 'info');
                        return;
                    }

                    this.startingRide = true;
                    let lat = 25.3176;
                    let lng = 82.9739;

                    if (navigator.geolocation) {
                        try {
                            const pos = await new Promise((resolve, reject) => {
                                navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 3500 });
                            });
                            lat = pos.coords.latitude;
                            lng = pos.coords.longitude;
                        } catch (e) {}
                    }

                    try {
                        const res = await fetch('/api/v1/tourist/ride/scan', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${this.auth.token}`
                            },
                            body: JSON.stringify({
                                qr_token: this.scannedDriver.qr_token,
                                scan_latitude: lat,
                                scan_longitude: lng
                            })
                        });

                        const data = await res.json();

                        if (res.ok && data.status) {
                            this.activeRide = data.data;
                            localStorage.setItem('lf_active_ride', JSON.stringify(data.data));
                            this.startRideTimer();
                            this.driverVerificationModal = false;
                            this.showToast('Ride logged safely! Have a pleasant journey.', 'success');
                            this.fetchTouristRides();
                        } else {
                            this.showToast(data.message || 'Could not log ride', 'error');
                        }
                    } catch (e) {
                        this.showToast('Network error while logging ride', 'error');
                    } finally {
                        this.startingRide = false;
                    }
                },

                async completeRide(rideId) {
                    this.completingRide = true;
                    try {
                        const res = await fetch(`/api/v1/tourist/rides/${rideId}/complete`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${this.auth.token}`
                            }
                        });
                        const data = await res.json();
                        if (res.ok && data.status) {
                            this.activeRide = null;
                            localStorage.removeItem('lf_active_ride');
                            this.showToast('Ride marked completed. Safe travels!', 'success');
                            this.fetchTouristRides();
                        } else {
                            this.showToast(data.message || 'Could not complete ride', 'error');
                        }
                    } catch (e) {
                        this.showToast('Error completing ride', 'error');
                    } finally {
                        this.completingRide = false;
                    }
                },

                openClaimModal(rideId) {
                    if (!this.auth.token) {
                        this.loginModal = true;
                        this.showToast('Please sign in to file a lost item claim', 'info');
                        return;
                    }
                    this.claimForm.rideId = rideId;
                    this.claimForm.category = this.categories[0]?.name || 'Wallet';
                    this.claimForm.description = '';
                    this.claimForm.bounty = '';
                    this.claimModal = true;
                },

                async submitLostClaim() {
                    this.submittingClaim = true;

                    const payload = {
                        ride_session_id: this.claimForm.rideId,
                        item_category: this.claimForm.category,
                        item_description: this.claimForm.description,
                        bounty_amount: this.claimForm.bounty ? parseFloat(this.claimForm.bounty) : 0
                    };

                    try {
                        const res = await fetch('/api/v1/tourist/claims', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${this.auth.token}`
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json();

                        if (res.ok && data.status) {
                            this.claimModal = false;
                            this.generatedOtp = data.data.handover_otp;
                            this.otpSuccessModal = true;
                            this.fetchTouristClaims();
                            this.fetchTouristRides();
                        } else {
                            this.showToast(data.message || 'Error submitting claim', 'error');
                        }
                    } catch (e) {
                        this.showToast('Network error filing claim', 'error');
                    } finally {
                        this.submittingClaim = false;
                    }
                },

                async cancelClaim(claimId) {
                    if (!confirm('Are you sure you want to cancel this claim?')) return;

                    try {
                        const res = await fetch(`/api/v1/tourist/claims/${claimId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${this.auth.token}`
                            }
                        });
                        const data = await res.json();
                        if (res.ok && data.status) {
                            this.showToast('Claim cancelled', 'info');
                            this.fetchTouristClaims();
                        } else {
                            this.showToast(data.message || 'Could not cancel claim', 'error');
                        }
                    } catch (e) {
                        this.showToast('Error cancelling claim', 'error');
                    }
                },

                async fetchTouristClaims() {
                    if (!this.auth.token) return;
                    this.loadingClaims = true;
                    try {
                        const res = await fetch('/api/v1/tourist/claims', {
                            headers: {
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${this.auth.token}`
                            }
                        });
                        const data = await res.json();
                        if (res.ok && data.status) {
                            this.claimsList = data.data.data || [];
                        }
                    } catch (e) {
                    } finally {
                        this.loadingClaims = false;
                    }
                },

                async fetchTouristRides() {
                    if (!this.auth.token) return;
                    this.loadingRides = true;
                    try {
                        const res = await fetch('/api/v1/tourist/rides', {
                            headers: {
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${this.auth.token}`
                            }
                        });
                        const data = await res.json();
                        if (res.ok && data.status) {
                            this.ridesList = data.data.data || [];
                        }
                    } catch (e) {
                    } finally {
                        this.loadingRides = false;
                    }
                },

                // Authentication
                async quickDemoLogin() {
                    this.loggingIn = true;
                    try {
                        const res = await fetch('/api/v1/auth/verify-otp', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                phone: '9123456789',
                                otp: '123456',
                                role: 'tourist'
                            })
                        });
                        const data = await res.json();
                        if (res.ok && data.status) {
                            this.handleLoginSuccess(data.data);
                        } else {
                            this.showToast(data.message || 'Demo login failed', 'error');
                        }
                    } catch (e) {
                        this.showToast('Network error during demo login', 'error');
                    } finally {
                        this.loggingIn = false;
                    }
                },

                async sendOtp() {
                    this.sendingOtp = true;
                    try {
                        const res = await fetch('/api/v1/auth/send-otp', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                phone: this.authForm.phone,
                                role: 'tourist'
                            })
                        });
                        const data = await res.json();
                        if (res.ok && data.status) {
                            this.authForm.otpSent = true;
                            const otpMsg = data.debug_otp ? `OTP is ${data.debug_otp}` : 'OTP sent to your phone';
                            this.showToast(otpMsg, 'info');
                        } else {
                            this.showToast(data.message || 'Failed to send OTP', 'error');
                        }
                    } catch (e) {
                        this.showToast('Error sending OTP', 'error');
                    } finally {
                        this.sendingOtp = false;
                    }
                },

                async verifyOtp() {
                    this.loggingIn = true;
                    try {
                        const res = await fetch('/api/v1/auth/verify-otp', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                phone: this.authForm.phone,
                                otp: this.authForm.otp,
                                role: 'tourist'
                            })
                        });
                        const data = await res.json();
                        if (res.ok && data.status) {
                            this.handleLoginSuccess(data.data);
                        } else {
                            this.showToast(data.message || 'Invalid OTP', 'error');
                        }
                    } catch (e) {
                        this.showToast('Error verifying OTP', 'error');
                    } finally {
                        this.loggingIn = false;
                    }
                },

                handleLoginSuccess(authData) {
                    this.auth.token = authData.token;
                    this.auth.user = authData.user;
                    localStorage.setItem('lf_token', authData.token);
                    localStorage.setItem('lf_user', JSON.stringify(authData.user));
                    this.loginModal = false;
                    this.showToast('Signed in successfully!', 'success');
                    this.fetchTouristClaims();
                    this.fetchTouristRides();
                },

                logout() {
                    this.auth.token = '';
                    this.auth.user = null;
                    localStorage.removeItem('lf_token');
                    localStorage.removeItem('lf_user');
                    localStorage.removeItem('lf_active_ride');
                    this.activeRide = null;
                    this.claimsList = [];
                    this.ridesList = [];
                    this.showToast('Signed out', 'info');
                },

                async recordBannerClick(bannerId) {
                    try {
                        fetch(`/api/v1/app/promotions/${bannerId}/click`, { method: 'POST' });
                    } catch (e) {}
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('en-IN', {
                        day: 'numeric',
                        month: 'short',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            }
        }
    </script>
</body>
</html>
