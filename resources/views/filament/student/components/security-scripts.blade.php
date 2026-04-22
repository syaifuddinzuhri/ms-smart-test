<script>
    const lockPortrait = async () => {
        try {
            if (screen.orientation && screen.orientation.lock) {
                // Coba kunci ke portrait-primary
                await screen.orientation.lock('portrait-primary');
            }
        } catch (err) {
            console.warn("Orientasi tidak bisa dikunci: ", err);
        }
    };

    // Panggil fungsi ini saat user klik tombol "Mulai Ujian" atau masuk ke halaman
    // Catatan: Biasanya butuh interaksi user (click) agar ini berfungsi
    window.addEventListener('click', () => {
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().then(lockPortrait);
        }
    }, {
        once: true
    }); // Jalankan sekali saja saat pertama klik

    window.addEventListener("orientationchange", function() {
        // Jika berubah ke landscape (90 atau -90 derajat)
        if (Math.abs(window.orientation) === 90) {
            // Cek jika ini mobile (lebar layar kecil)
            if (window.innerWidth < 1000) {
                window.triggerLock(); // Langsung kunci ujian
            }
        }
    });

    // Versi modern
    if (screen.orientation) {
        screen.orientation.addEventListener('change', function(e) {
            if (e.currentTarget.type.startsWith('landscape') && window.innerWidth < 1000) {
                window.triggerLock();
            }
        });
    }


    window.addEventListener('pagehide', () => {
        if (!window.isNavigatingAllowed) window.triggerLock();
    });

    // Deteksi saat sistem membekukan tab (biasanya saat buka app lain di floating mode)
    document.addEventListener('freeze', () => {
        window.triggerLock();
    });

    document.querySelectorAll('video').forEach(video => {
        video.disablePictureInPicture = true;
        video.setAttribute('controlslist', 'nodownload noplaybackrate');
    });

    // Mencegah seret/drag teks ke jendela lain (floating)
    document.addEventListener('dragstart', (e) => {
        e.preventDefault();
        window.triggerLock();
    });

    // Deteksi jika ukuran layar berubah mendadak (ciri-ciri split screen atau resize ke floating)
    window.addEventListener('resize', () => {
        if (window.isPageReady && !window.isNavigatingAllowed) {
            // Jika lebar atau tinggi berkurang lebih dari 20% secara mendadak, kunci!
            // Atau Anda bisa paksa ukuran minimal.
            if (window.innerWidth < 600 || window.innerHeight < 400) {
                window.triggerLock();
            }
        }
    });
</script>
