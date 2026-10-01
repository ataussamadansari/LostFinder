<!-- Dedicated Modular Alpine.js Application Logic -->
<script>
function lostFinderApp(initialConfig = {}) {
    return {
        activeTab: 'home',
        categories: initialConfig.categories || [],
        promotions: initialConfig.promotions || [],
        currentBannerIndex: 0,
        serverConfig: initialConfig.serverConfig || {
            maintenance: { is_active: false, message: '' },
            feature_flags: { enable_bounties: true, enable_sos_emergency: true, enable_promotions: true },
            emergency_contacts: { police: '112', tourist: '1363', traffic: '1095', support: '+91 98765 00000' }
        },
        emergencyContacts: initialConfig.serverConfig?.emergency_contacts || {
            police: initialConfig.policeNumber || '112',
            tourist: initialConfig.touristHelpline || '1363',
            support: initialConfig.supportPhone || '+91 98765 00000'
        },

        // Modals & Drawers
        scannerModal: false,
        scannerLoading: false,
        html5QrScanner: null,
        manualCodeModal: false,
        manualToken: '',
        driverVerificationModal: false,
        fetchingDriverInfo: false,
        startingRide: false,
        scannedDriver: null,

        // Active Ride State
        activeRide: null,
        rideDuration: 'Just started',
        rideTimerInterval: null,
        completingRide: false,

        // Claims State
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

        // Rides History State
        ridesList: [],
        loadingRides: false,

        // SOS Drawer
        sosDrawer: false,

        // User Profile State
        profileModal: false,
        savingProfile: false,
        profileData: {
            masked_alias: '',
            total_rides: 0,
            total_claims: 0
        },
        profileForm: {
            name: '',
            emergency_contact_phone: ''
        },

        // Authentication State
        auth: {
            token: localStorage.getItem('lf_token') || '',
            user: JSON.parse(localStorage.getItem('lf_user') || 'null')
        },
        loginModal: false,
        authForm: {
            phone: '9123456789',
            otp: '',
            otpSent: false
        },
        sendingOtp: false,
        loggingIn: false,

        // Toast Notifications
        toast: {
            show: false,
            message: '',
            type: 'info'
        },

        showToast(message, type = 'info') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => {
                this.toast.show = false;
            }, 3500);
        },

        initApp() {
            // Check if server pre-loaded a scanned driver from URL /ride/qr/{token}
            if (initialConfig.scannedDriver) {
                const pre = initialConfig.scannedDriver;
                this.scannedDriver = {
                    driver_name: pre.user?.name || 'Verified Driver',
                    vehicle_number: pre.vehicle_number,
                    vehicle_type: pre.vehicle_type,
                    profile_picture_url: pre.user?.profile_picture_url || null,
                    total_trips: pre.total_trips || 0,
                    qr_token: pre.qr_code_token
                };
                this.driverVerificationModal = true;
            }

            // Restore active ride from local storage if available
            const savedRide = localStorage.getItem('lf_active_ride');
            if (savedRide) {
                try {
                    this.activeRide = JSON.parse(savedRide);
                    this.startRideTimer();
                } catch (e) {}
            }

            // If user already authenticated, fetch their rides, claims, and profile
            if (this.auth.token) {
                this.fetchTouristClaims();
                this.fetchTouristRides();
                this.fetchProfile();
            }

            // Rotate banner if multiple promotions exist
            if (this.promotions.length > 1) {
                setInterval(() => {
                    this.currentBannerIndex = (this.currentBannerIndex + 1) % this.promotions.length;
                }, 6000);
            }
        },

        startRideTimer() {
            if (this.rideTimerInterval) clearInterval(this.rideTimerInterval);
            this.calculateRideDuration();
            this.rideTimerInterval = setInterval(() => {
                this.calculateRideDuration();
            }, 30000);
        },

        calculateRideDuration() {
            if (!this.activeRide || !this.activeRide.logged_at) return;
            const start = new Date(this.activeRide.logged_at).getTime();
            const now = new Date().getTime();
            const diffMin = Math.max(0, Math.floor((now - start) / 60000));
            this.rideDuration = diffMin <= 1 ? 'Just started' : `${diffMin} min ago`;
        },

        // QR Code Scanner Handlers
        openQrScanner() {
            this.scannerModal = true;
            this.scannerLoading = true;

            this.$nextTick(() => {
                try {
                    if (typeof Html5Qrcode === 'undefined') {
                        this.scannerLoading = false;
                        this.showToast('Scanner library loading... Please use manual code entry', 'info');
                        return;
                    }
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
                    }).catch((err) => {
                        this.scannerLoading = false;
                        console.warn('Camera error:', err);
                        this.showToast('Camera not accessible. Please enter code manually below.', 'info');
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
                    try { this.html5QrScanner.clear(); } catch (e) {}
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
                this.showToast('Please enter a valid driver QR code or token', 'error');
                return;
            }
            this.manualCodeModal = false;
            this.handleScannedToken(token);
        },

        async handleScannedToken(rawToken) {
            const token = this.extractToken(rawToken);
            this.fetchingDriverInfo = true;

            try {
                const res = await fetch(`/api/v1/ride/driver-info/${token}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (res.ok && data.status) {
                    this.scannedDriver = data.data;
                    this.driverVerificationModal = true;
                } else {
                    this.showToast(data.message || 'Driver QR not found in registered database', 'error');
                }
            } catch (e) {
                this.showToast('Network error while looking up driver QR', 'error');
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
                    if (this.rideTimerInterval) clearInterval(this.rideTimerInterval);
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
            this.claimForm.category = this.categories[0]?.name || 'Luggage / Bag';
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
                    this.showToast('Claim cancelled successfully', 'info');
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
                    this.claimsList = data.data?.data || [];
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
                    this.ridesList = data.data?.data || [];
                }
            } catch (e) {
            } finally {
                this.loadingRides = false;
            }
        },

        // Profile Management
        openProfileModal() {
            this.profileModal = true;
            this.fetchProfile();
        },

        async fetchProfile() {
            if (!this.auth.token) return;
            try {
                const res = await fetch('/api/v1/tourist/profile', {
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${this.auth.token}`
                    }
                });
                const data = await res.json();
                if (res.ok && data.status && data.data) {
                    this.profileData = data.data;
                    this.profileForm.name = data.data.name || '';
                    this.profileForm.emergency_contact_phone = data.data.emergency_contact_phone || '';
                }
            } catch (e) {}
        },

        async updateProfile() {
            this.savingProfile = true;
            try {
                const res = await fetch('/api/v1/tourist/profile', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${this.auth.token}`
                    },
                    body: JSON.stringify({
                        name: this.profileForm.name,
                        emergency_contact_phone: this.profileForm.emergency_contact_phone
                    })
                });
                const data = await res.json();
                if (res.ok && data.status) {
                    this.showToast('Profile updated successfully!', 'success');
                    if (this.auth.user) {
                        this.auth.user.name = this.profileForm.name;
                        this.auth.user.emergency_contact_phone = this.profileForm.emergency_contact_phone;
                        localStorage.setItem('lf_user', JSON.stringify(this.auth.user));
                    }
                    this.fetchProfile();
                } else {
                    this.showToast(data.message || 'Failed to update profile', 'error');
                }
            } catch (e) {
                this.showToast('Error saving profile changes', 'error');
            } finally {
                this.savingProfile = false;
            }
        },

        // Robust Authentication Handlers
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
                    this.handleLoginSuccess(data);
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
            const phone = this.authForm.phone.trim();
            if (!phone) {
                this.showToast('Please enter your mobile number', 'error');
                return;
            }

            this.sendingOtp = true;
            try {
                const res = await fetch('/api/v1/auth/send-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        phone: phone,
                        role: 'tourist'
                    })
                });
                const data = await res.json();
                if (res.ok && data.status) {
                    this.authForm.otpSent = true;
                    if (data.debug_otp) {
                        this.authForm.otp = data.debug_otp;
                    }
                    const otpMsg = data.debug_otp ? `Use Test OTP: ${data.debug_otp}` : 'OTP sent to your phone';
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
            const phone = this.authForm.phone.trim();
            const otp = this.authForm.otp.trim();
            if (!phone || !otp) {
                this.showToast('Please enter phone and 6-digit OTP', 'error');
                return;
            }

            this.loggingIn = true;
            try {
                const res = await fetch('/api/v1/auth/verify-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        phone: phone,
                        otp: otp,
                        role: 'tourist'
                    })
                });
                const data = await res.json();
                if (res.ok && data.status) {
                    this.handleLoginSuccess(data);
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
            // Support both top-level token and nested data.token
            const token = authData.token || authData?.data?.token || '';
            const user = authData.user || authData?.data?.user || null;

            this.auth.token = token;
            this.auth.user = user;

            if (token) localStorage.setItem('lf_token', token);
            if (user) localStorage.setItem('lf_user', JSON.stringify(user));

            this.loginModal = false;
            this.authForm.otp = '';
            this.authForm.otpSent = false;
            this.showToast('Signed in successfully!', 'success');

            this.fetchTouristClaims();
            this.fetchTouristRides();
            this.fetchProfile();
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
            if (this.rideTimerInterval) clearInterval(this.rideTimerInterval);
            this.showToast('Signed out successfully', 'info');
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
    };
}
</script>
