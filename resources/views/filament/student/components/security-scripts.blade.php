<!-- 1. STYLE CSS GLOBAL -->
<style>
    body {
        -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none;
    }
    input, textarea {
        -webkit-user-select: text !important; -moz-user-select: text !important;
        -ms-user-select: text !important; user-select: text !important; cursor: text !important;
    }

    /* Portrait Lock Overlay */
    @media screen and (orientation: landscape) and (max-height: 500px),
    screen and (orientation: landscape) and (max-width: 950px) {
        #portrait-lock-message { display: flex !important; }
        body { overflow: hidden; }
    }

    #portrait-lock-message, #security-logout-popup {
        display: none; position: fixed; inset: 0; background: #ffffff;
        z-index: 9999999; flex-direction: column; align-items: center;
        justify-content: center; text-align: center; padding: 20px;
    }

    /* Popup Logout Khusus Pelanggaran di Luar Ujian */
    #security-logout-popup {
        background: rgba(0, 0, 0, 0.9); color: white;
    }

    #floating-refresh-btn {
        position: fixed; bottom: 20px; right: 20px; z-index: 9999990;
        display: flex; align-items: center; justify-content: center;
        width: 50px; height: 50px; background-color: #10b981; color: white;
        border-radius: 9999px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        cursor: pointer; border: none; transition: all 0.3s ease; opacity: 0.8;
    }

    .spinning { animation: spin 1s linear infinite; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<!-- 2. KOMPONEN HTML -->
<button id="floating-refresh-btn" onclick="handleManualRefresh()" title="Refresh Halaman">
    <svg id="refresh-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
    </svg>
</button>

<!-- Pesan Portrait -->
<div id="portrait-lock-message">
    <div class="mb-4">
        <svg class="w-16 h-16 text-emerald-600 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
        </svg>
    </div>
    <h2 class="text-xl font-bold text-gray-900">Gunakan Mode Portrait</h2>
    <p class="text-gray-500 mt-2">Ujian ini hanya dapat dikerjakan dalam orientasi portrait.</p>
</div>

<!-- Popup Logout Otomatis -->
<div id="security-logout-popup">
    <div class="bg-white text-gray-900 p-8 rounded-3xl max-w-sm w-full shadow-2xl">
        <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-black mb-2 text-red-600">AKSES DIBLOKIR</h2>
        <p class="text-gray-500 font-medium mb-6">Aktivitas mencurigakan terdeteksi (Floating App/Split Screen). Anda akan dikeluarkan dari sistem dalam <span id="logout-timer" class="font-bold text-red-600">3</span> detik.</p>
    </div>
</div>


<script>
    // --- GLOBAL REFRESH ---
    window.handleManualRefresh = function() {
        window.isReloading = true;
        window.isNavigatingAllowed = true;
        const icon = document.getElementById('refresh-icon');
        if (icon) icon.classList.add('spinning');
        setTimeout(() => window.location.reload(), 200);
    };

    (function() {
        const shield = document.getElementById('interaction-shield');
        const logoutPopup = document.getElementById('security-logout-popup');
        
        // --- LOGIC DETEKSI HALAMAN ---
        const isExamPage = () => window.location.pathname.includes('/start-test'); 
        const isLoginPage = () => window.location.pathname.endsWith('/login'); // Cek halaman login
        
        const isMobile = () => ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

        const activateShield = (state) => {
            if (shield) shield.style.display = state ? 'block' : 'none';
            if (state) document.activeElement.blur();
        };

        const handleViolation = () => {
            // SKIP JIKA DI HALAMAN LOGIN
            if (isLoginPage()) return;

            if (window.isNavigatingAllowed || window.isReloading) return;

            if (isExamPage()) {
                if (typeof window.triggerLock === 'function') window.triggerLock();
            } else {
                triggerAutoLogout();
            }
        };

        let logoutStarted = false;
        const triggerAutoLogout = () => {
            // JANGAN LOGOUT JIKA DI HALAMAN LOGIN
            if (isLoginPage()) return;

            if (logoutStarted) return;
            logoutStarted = true;

            if (logoutPopup) logoutPopup.style.display = 'flex';
            const timerEl = document.getElementById('logout-timer');

            let timeLeft = 10;
            const interval = setInterval(() => {
                timeLeft--;
                if (timerEl) timerEl.innerText = timeLeft;
                if (timeLeft <= 0) {
                    clearInterval(interval);
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/logout';
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';
                    form.appendChild(csrf);
                    document.body.appendChild(form);
                    form.submit();
                }
            }, 1000);
        };

        // --- DETEKSI AGRESIF FLOATING APP ---
        setInterval(() => {
            // MATIKAN SEMUA LOGIC JIKA DI HALAMAN LOGIN
            if (isLoginPage()) {
                activateShield(false);
                return;
            }

            if (window.isNavigatingAllowed || window.isReloading || logoutStarted) return;

            const isFocusLost = !document.hasFocus();
            const isTyping = ['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName);
            
            let viewportViolation = false;
            if (isMobile() && window.visualViewport && !isTyping) {
                viewportViolation = window.visualViewport.height < (window.innerHeight * 0.75);
            }

            if (isFocusLost || viewportViolation) {
                activateShield(true);
                handleViolation();
            } else {
                if (logoutPopup && logoutPopup.style.display !== 'flex') {
                    activateShield(false);
                }
            }
        }, 500);

        // --- EVENT HANDLERS ---
        window.addEventListener('keydown', function(e) {
            // Jangan blokir keyboard di halaman login agar user bisa ketik user/pass dengan lancar
            if (isLoginPage()) return;

            const key = e.key.toLowerCase();
            const isCtrl = e.ctrlKey || e.metaKey;
            if (e.keyCode === 116 || (isCtrl && key === 'r')) e.preventDefault();
            if (isCtrl && ['s', 'p', 'u'].includes(key)) e.preventDefault();
            if (e.keyCode === 123) e.preventDefault();
        });

        const checkOrientation = () => {
            if (!isMobile() || isLoginPage()) return; // Skip di login
            
            if (window.innerWidth > window.innerHeight && window.innerHeight < 600) {
                const portraitMsg = document.getElementById('portrait-lock-message');
                if (portraitMsg) portraitMsg.style.setProperty('display', 'flex', 'important');
                handleViolation();
            } else {
                const portraitMsg = document.getElementById('portrait-lock-message');
                if (portraitMsg) portraitMsg.style.display = 'none';
            }
        };

        document.addEventListener('contextmenu', e => {
            if (!isLoginPage()) e.preventDefault();
        });

        document.addEventListener('copy', e => {
            if (!isLoginPage()) e.preventDefault();
        });

        document.addEventListener('dragstart', e => { 
            if (isLoginPage()) return;
            e.preventDefault(); 
            handleViolation(); 
        });

        window.addEventListener("orientationchange", () => setTimeout(checkOrientation, 300));
        window.addEventListener('resize', checkOrientation);

        window.addEventListener('pagehide', () => handleViolation());
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) handleViolation();
        });

        // Initialize
        checkOrientation();
        document.addEventListener('livewire:navigated', () => {
            checkOrientation();
            logoutStarted = false;
            if (logoutPopup) logoutPopup.style.display = 'none';
            activateShield(false);
        });
    })();
</script>