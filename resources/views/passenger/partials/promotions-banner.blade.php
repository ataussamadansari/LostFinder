<!-- Promotions & Alerts Banner (Server-Driven) -->
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
