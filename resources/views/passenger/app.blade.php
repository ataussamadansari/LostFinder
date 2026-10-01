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

    <!-- Dedicated Inline Scripts Partial (100% immune to 404 static file errors) -->
    @include('passenger.partials.scripts')

    <!-- Alpine.js Core CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        /* Custom sleek scrollbar */
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
        .safe-area-pb {
            padding-bottom: env(safe-area-inset-bottom, 0.75rem);
        }
    </style>
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased selection:bg-indigo-500 selection:text-white"
      x-data="lostFinderApp({
          categories: @json($categories),
          promotions: @json($promotions),
          serverConfig: @json($serverConfig),
          emergencyContacts: {
              police: '{{ $policeNumber }}',
              tourist: '{{ $touristHelpline }}',
              support: '{{ $supportPhone }}'
          },
          scannedDriver: @json($scannedDriver)
      })"
      x-init="initApp()">

    <!-- Responsive Container -->
    <div class="min-h-screen bg-slate-950 flex flex-col relative pb-24 md:pb-8">

        <!-- Server-Driven Maintenance Banner (If Enabled by Admin) -->
        <template x-if="serverConfig?.maintenance?.is_active">
            <div class="bg-amber-600/20 border-b border-amber-500/40 px-4 py-2.5 text-center text-xs sm:text-sm text-amber-200 font-semibold flex items-center justify-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-400"></i>
                <span x-text="serverConfig?.maintenance?.message || 'LostFinder is currently undergoing scheduled maintenance.'"></span>
            </div>
        </template>

        <!-- 1. Modular Header -->
        @include('passenger.partials.header')

        <!-- Main Content Area -->
        <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-5">
            <!-- 2. Promotions & Alerts Banner -->
            @include('passenger.partials.promotions-banner')

            <!-- 3. Tab: Home & Scanner Hero -->
            @include('passenger.partials.tab-home')

            <!-- 4. Tab: Lost Items & Handover OTP Claims -->
            @include('passenger.partials.tab-claims')

            <!-- 5. Tab: Rides History -->
            @include('passenger.partials.tab-rides')
        </main>

        <!-- 6. 5-Item Mobile Bottom Navigation Bar (Home | SOS | Camera | Lost Items | Rides) -->
        @include('passenger.partials.bottom-nav')

        <!-- Modals & Drawers -->
        @include('passenger.partials.modals.scanner-modal')
        @include('passenger.partials.modals.manual-code-modal')
        @include('passenger.partials.modals.driver-verify-modal')
        @include('passenger.partials.modals.claim-form-modal')
        @include('passenger.partials.modals.otp-success-modal')
        @include('passenger.partials.modals.sos-drawer')
        @include('passenger.partials.modals.auth-modal')
        @include('passenger.partials.modals.profile-modal')

        <!-- Toast Notifications -->
        @include('passenger.partials.toast')

    </div>

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(err => {
                    console.log('SW registration skipped', err);
                });
            });
        }
    </script>
</body>
</html>
