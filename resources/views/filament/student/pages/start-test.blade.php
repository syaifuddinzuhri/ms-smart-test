<x-filament-panels::page>
    <div x-data="{
        isLocked: @entangle('isLocked'),

        lockExam() {
            // Hanya jalankan lock jika:
            // 1. Belum terkunci
            // 2. Halaman sudah siap (lewat masa tenggang 2 detik)
            // 3. Bukan karena proses reload/refresh halaman
            if (!this.isLocked && window.isPageReady && !window.isReloading) {
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
            <div
                class="fixed inset-0 z-[999998] bg-black/60 backdrop-blur-md flex items-center justify-center p-6 text-center">
                <div class="bg-white p-8 rounded-2xl max-w-sm shadow-2xl">
                    <div class="text-amber-500 mb-4 animate-bounce">
                        <x-heroicon-o-arrows-pointing-out class="w-16 h-16 mx-auto" />
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-gray-900 uppercase tracking-tight">
                        Fokus Mode Aktif
                    </h3>

                    <div class="space-y-3 mb-6">
                        <p class="text-gray-600 text-sm leading-relaxed px-2">
                            Untuk menjaga integritas, Anda wajib mengerjakan ujian dalam
                            <span class="font-bold text-gray-800">Tampilan Layar Penuh (DESKTOP)</span>.
                            Jangan keluar dari halaman ini sebelum selesai.
                        </p>

                        <div
                            class="bg-red-50 border border-red-100 rounded-xl p-3 flex items-center gap-3 justify-center mx-auto">
                            <div class="relative flex h-3 w-3">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-600"></span>
                            </div>
                            <p class="text-[11px] sm:text-xs font-black text-red-700 uppercase tracking-widest">
                                Waktu ujian terus berjalan!
                            </p>
                        </div>
                    </div>
                    <x-filament::button size="lg" color="warning" class="w-full"
                        @click="triggerFullScreen(); showFullscreenOverlay = false">
                        MULAI KERJAKAN
                    </x-filament::button>
                    <p class="mt-4 text-[10px] text-gray-400 uppercase font-medium">
                        MANUSGI SMART TEST • SECURITY PROTOCOL
                    </p>
                </div>
            </div>
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
                if (window.isPageReady && !window.isLockedFromDB && !window.isReloading) {
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
