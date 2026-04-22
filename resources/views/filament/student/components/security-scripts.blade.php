<script>
    (function() {
        const lockPortrait = async () => {
            try {
                if (screen.orientation && screen.orientation.lock) {
                    await screen.orientation.lock('portrait-primary');
                }
            } catch (err) {
                console.warn("Orientasi tidak bisa dikunci secara native.");
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
                document.documentElement.requestFullscreen().then(lockPortrait).catch(() => {});
            }
        }, {
            once: true
        });

        const handleOrientationChange = () => {
            if (window.innerWidth < 1000) {
                const isLandscape = window.innerWidth > window.innerHeight;
                if (isLandscape) {
                    window.triggerLock();
                }
            }
        };

        if (screen.orientation) {
            screen.orientation.addEventListener('change', (e) => {
                if (e.currentTarget.type.startsWith('landscape') && window.innerWidth < 1000) {
                    window.triggerLock();
                }
            });
        }
        window.addEventListener("orientationchange", handleOrientationChange);

        // --- DETEKSI FLOATING / SPLIT SCREEN / TAB SWITCH ---
        window.addEventListener('resize', () => {
            // Deteksi perubahan ukuran mendadak (ciri floating/split)
            if (window.innerWidth < 600 || window.innerHeight < 400) {
                window.triggerLock();
            }
        });

        window.addEventListener('pagehide', () => window.triggerLock());
        document.addEventListener('freeze', () => window.triggerLock());

        // --- PROTEKSI MEDIA ---
        const secureMedia = () => {
            document.querySelectorAll('video').forEach(video => {
                video.disablePictureInPicture = true;
                video.setAttribute('controlslist', 'nodownload noplaybackrate');
            });
        };
        secureMedia();

        // Jalankan ulang saat ada perubahan DOM (Livewire navigation)
        document.addEventListener('livewire:navigated', () => {
            secureMedia();
        });

    })();
</script>
