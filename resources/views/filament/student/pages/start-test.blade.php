<x-filament-panels::page>
    <div x-data="{
        isLocked: @entangle('isLocked'),

        lockExam() {
            // Hanya jalankan lock jika:
            // 1. Belum terkunci
            // 2. Halaman sudah siap (lewat masa tenggang 2 detik)
            // 3. Bukan karena proses reload/refresh halaman
            if (!this.isLocked && window.isPageReady && !window.isNavigatingAllowed && !window.isReloading) {
                $wire.call('lockExam');
            }
        }
    }"
        @keydown.window="
        const key = $event.key.toLowerCase();
        const isCmdOrCtrl = $event.ctrlKey || $event.metaKey;

        // Izinkan reload
        if (key === 'f5' || (isCmdOrCtrl && key === 'r')) {
            window.isReloading = true;
            return;
        }

        // Blokir shortcut curang
        if (
            (isCmdOrCtrl && ['t', 'n', 'u', 'i', 'j', 'p', 'e', 'k'].includes(key)) ||
            (isCmdOrCtrl && $event.shiftKey && ['n', 'i', 'j'].includes(key)) ||
            $event.key === 'F12' ||
            ($event.metaKey && key !== 'r')
        ) {
            $event.preventDefault();
            lockExam();
        }
    "
        @visibilitychange.window="if (document.hidden) lockExam()"
        @blur.window="setTimeout(() => { if (!document.hasFocus()) lockExam() }, 200);" class="relative">

        {{-- <template x-if="showFullscreenOverlay && !isLocked">
            @include('filament.student.pages.parts.fullscreen-overlay')
        </template> --}}

        @if ($isLocked)
            @include('filament.student.pages.parts.lock-overlay')
        @endif

        <div :style="isLocked ? 'filter: blur(20px); pointer-events: none;' : ''">
            @include('filament.student.pages.parts.exam-content')
        </div>
    </div>


    @push('scripts')
        <script>
            // Inisialisasi variabel global
            window.isLockedFromDB = @js($isLocked);
            window.isPageReady = false;
            window.isReloading = false;
            window.isNavigatingAllowed = false; // Flag untuk navigasi resmi
            const lockHistory = () => {
                if (window.isNavigatingAllowed) return;
                const currentUrl = window.location.href.split('#')[0];
                if (window.location.hash !== '#locked') {
                    window.history.pushState({
                        locked: true
                    }, "", currentUrl + "#locked");
                }
            };

            // Inisialisasi
            window.history.replaceState({
                root: true
            }, "", window.location.href);
            lockHistory();

            // Re-lock saat interaksi (Anti-Reload)
            const reLockHandler = () => lockHistory();
            ['mousedown', 'keydown', 'touchstart'].forEach(event => {
                window.addEventListener(event, reLockHandler);
            });

            // Tangkap Back
            window.addEventListener('popstate', function(event) {
                if (window.isNavigatingAllowed) return;
                lockHistory();
                @this.call('mountAction', 'submit');
            });

            /**
             * FUNGSI UNTUK MEMBUKA KUNCI
             */
            const disableLock = () => {
                window.isNavigatingAllowed = true;
                // Hapus listener agar tidak berat
                ['mousedown', 'keydown', 'touchstart'].forEach(event => {
                    window.removeEventListener(event, reLockHandler);
                });
                // Bersihkan Hash URL
                const cleanUrl = window.location.href.split('#')[0];
                window.history.replaceState(null, "", cleanUrl);
            };

            // Trigger dari JS Manual (tombol x-on:click)
            window.prepareNavigation = disableLock;

            // Trigger dari PHP / Livewire (Dispatch dari Server)
            window.addEventListener('prepare-navigation', disableLock);


            // LOGIKA PROTEKSI

            // Berikan jeda agar tidak langsung mengunci saat halaman baru dimuat (refresh)
            setTimeout(() => {
                window.isPageReady = true;
            }, 1000);

            // Tandai jika sedang proses reload (agar tidak terkunci saat refresh)
            window.addEventListener('beforeunload', () => {
                window.isReloading = true;
            });

            // Deteksi tombol reload manual (F5 / Ctrl+R)
            window.addEventListener('keydown', (e) => {
                const key = e.key.toLowerCase();
                if (key === 'f5' || ((e.ctrlKey || e.metaKey) && key === 'r')) {
                    window.isReloading = true;
                }
            });

            // Fungsi helper untuk lock (dipakai oleh auxclick/click manual)
            window.triggerLock = function() {
                if (window.isPageReady && !window.isLockedFromDB && !window.isReloading && !window.isNavigatingAllowed) {
                    @this.call('lockExam');
                }
            };

            // Proteksi klik kanan/tengah/ctrl+klik
            document.addEventListener('auxclick', (e) => {
                if (e.button === 1) triggerLock();
            });
            document.addEventListener('click', (e) => {
                if (e.ctrlKey || e.metaKey) triggerLock();
            });
        </script>
    @endpush
</x-filament-panels::page>
