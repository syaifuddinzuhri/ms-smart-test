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
        @blur.window="
        if (!window.isNavigatingAllowed) {
            // Berikan delay sangat singkat untuk memastikan bukan sekadar flicker
            setTimeout(() => {
                if (!document.hasFocus()) {
                    lockExam();
                }
            }, 100);
        }
    "
        @focus.window="
        // Jika kembali fokus tapi sempat kehilangan fokus, cek validitas
        if (!window.isNavigatingAllowed && !document.hasFocus()) {
            lockExam();
        }
    "
        class="relative">

        {{-- <template x-if="showFullscreenOverlay && !isLocked">
            @include('filament.student.pages.parts.fullscreen-overlay')
        </template> --}}

        @if ($isLocked)
            @include('filament.student.pages.parts.lock-overlay')
        @endif

        <div :style="isLocked ? 'filter: blur(20px); pointer-events: none;' : ''">
            <div class="flex items-center md:items-start flex-col md:flex-row gap-3 gap-y-4 mb-4 w-full">
                <img src="{{ asset('images/logo.webp') }}" class="md:h-10 md:w-auto w-14">
                <div class="flex flex-col items-center md:items-start gap-y-1 w-full">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $exam->title }}
                    </h1>
                    <div
                        class="flex items-center gap-y-2 flex-col md:flex-row md:items-center md:justify-between w-full">
                        <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 font-medium">
                            {{ $exam->category?->name }} |
                            {{ $exam->subject?->name }} |
                            <span>
                                {{ $exam->classrooms->pluck('code')->join(', ') }}
                            </span>
                        </p>
                        @include('components.realtime-server-time-test')
                    </div>
                </div>
            </div>
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
    @endpush
</x-filament-panels::page>
