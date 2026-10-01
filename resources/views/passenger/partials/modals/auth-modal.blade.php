<!-- Authentication Modal (Phone OTP & 1-Tap Demo Access) -->
<div x-show="loginModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md"
     x-transition>
    <div @click.away="loginModal = false"
         class="w-full max-w-sm sm:max-w-md rounded-3xl bg-slate-900 border border-slate-800 p-6 sm:p-8 space-y-5 shadow-2xl relative">

        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <h3 class="text-base sm:text-lg font-bold text-white">Tourist Sign In</h3>
            </div>
            <button @click="loginModal = false" class="text-slate-400 hover:text-white p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- 1-Tap Quick Demo Login -->
        <div class="p-4 rounded-2xl bg-indigo-950/40 border border-indigo-500/40 space-y-2.5">
            <div class="flex items-center justify-between">
                <span class="text-xs sm:text-sm font-bold text-indigo-300">Quick Demo Access</span>
                <span class="text-[10px] bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded font-mono font-bold">1-Tap</span>
            </div>
            <p class="text-xs text-slate-300">
                Instantly sign in with verified Tourist test account (Alice Johnson).
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
                    <label class="text-xs font-semibold text-slate-300 flex items-center justify-between">
                        <span>6-Digit Verification OTP</span>
                        <span class="text-[11px] text-indigo-400">Default Test: 123456</span>
                    </label>
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
                        class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs sm:text-sm transition flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                    <span x-text="sendingOtp ? 'Sending OTP...' : 'Send Verification OTP'"></span>
                </button>
            </template>

            <template x-if="authForm.otpSent">
                <button @click="verifyOtp()"
                        :disabled="!authForm.otp.trim() || loggingIn"
                        class="w-full py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-emerald-600/30 transition flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-shield-check"></i>
                    <span x-text="loggingIn ? 'Verifying...' : 'Verify OTP & Continue'"></span>
                </button>
            </template>
        </div>
    </div>
</div>
