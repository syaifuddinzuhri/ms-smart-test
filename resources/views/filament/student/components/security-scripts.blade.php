<script>
    (function() {
        // --- HELPER DETEKSI ---
        const isMobile = () => ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

        const checkOrientation = () => {
            // Jika bukan mobile (Desktop), abaikan proteksi orientasi
            if (!isMobile()) return;

            const isLandscape = window.innerWidth > window.innerHeight;

            // HP Landscape biasanya memiliki tinggi layar < 500px saat dimiringkan
            // Kita gunakan threshold 600px untuk menangkap semua jenis HP/Tablet kecil
            if (isLandscape && window.innerHeight < 600) {
                // Tampilkan Overlay CSS (jika ada)
                const lockMessage = document.getElementById('portrait-lock-message');
                if (lockMessage) lockMessage.style.setProperty('display', 'flex', 'important');

                // Picu penguncian ujian
                window.triggerLock();
            } else {
                const lockMessage = document.getElementById('portrait-lock-message');
                if (lockMessage) lockMessage.style.display = 'none';
            }
        };

        const lockPortrait = async () => {
            try {
                if (screen.orientation && screen.orientation.lock) {
                    await screen.orientation.lock('portrait-primary');
                }
            } catch (err) {
                console.warn("Native lock failed, falling back to manual detection.");
            }
        };

        // --- PROTEKSI KEYBOARD & COPY-PASTE ---
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('copy', e => e.preventDefault());
        document.addEventListener('cut', e => e.preventDefault());
        document.addEventListener('paste', e => {
            if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });
        document.addEventListener('dragstart', e => e.preventDefault());

        // --- PROTEKSI FULLSCREEN & ORIENTASI ---
        window.addEventListener('click', () => {
            if (document.documentElement.requestFullscreen && !document.fullscreenElement) {
                document.documentElement.requestFullscreen()
                    .then(lockPortrait)
                    .catch(() => {});
            }
        }, {
            once: true
        });

        // Handler untuk perubahan orientasi (Android & iOS)
        window.addEventListener("orientationchange", () => {
            // Berikan sedikit delay karena Android butuh waktu update innerWidth/Height
            setTimeout(checkOrientation, 300);
        });

        // Handler modern untuk Android Chrome
        if (screen.orientation) {
            screen.orientation.addEventListener('change', checkOrientation);
        }

        // --- DETEKSI FLOATING / SPLIT SCREEN / RESIZE ---
        window.addEventListener('resize', () => {
            // Jalankan cek orientasi saat resize (sering terjadi di Android)
            checkOrientation();

            // Deteksi tambahan: Jika ukuran layar sangat kecil mendadak (Floating App)
            // Kecuali jika sedang mengetik (keyboard muncul biasanya mengurangi tinggi)
            const isTyping = document.activeElement.tagName === 'INPUT' || document.activeElement
                .tagName === 'TEXTAREA';
            if (!isTyping) {
                if (window.innerWidth < 350 || window.innerHeight < 300) {
                    window.triggerLock();
                }
            }
        });

        // --- DETEKSI PINDAH TAB / MINIMIZE ---
        window.addEventListener('pagehide', () => window.triggerLock());
        document.addEventListener('freeze', () => window.triggerLock());
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) window.triggerLock();
        });

        // --- PROTEKSI MEDIA ---
        const secureMedia = () => {
            document.querySelectorAll('video').forEach(video => {
                video.disablePictureInPicture = true;
                video.setAttribute('controlslist', 'nodownload noplaybackrate');
            });
        };

        // Inisialisasi
        secureMedia();
        checkOrientation();

        document.addEventListener('livewire:navigated', () => {
            secureMedia();
            checkOrientation();
        });

    })();
</script>
