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
