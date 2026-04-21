<div class="fixed inset-0 z-[999998] bg-black/60 backdrop-blur-md flex items-center justify-center p-6 text-center">
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

            <div class="bg-red-50 border border-red-100 rounded-xl p-3 flex items-center gap-3 justify-center mx-auto">
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
            MS SMART TEST • SECURITY PROTOCOL
        </p>
    </div>
</div>
