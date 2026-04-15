<x-filament-panels::page>
    <div x-data="{
        isLocked: @entangle('isLocked'),
        lockExam() {
            if (!this.isLocked) {
                $wire.call('lockExam');
            }
        }
    }" @visibilitychange.window="if (document.hidden) lockExam()" @blur.window="lockExam()"
        @keydown.window="
        if ($event.keyCode == 123 || ($event.ctrlKey && $event.shiftKey && $event.keyCode == 73) || ($event.ctrlKey && $event.keyCode == 85) || $event.metaKey) {
            lockExam();
            $event.preventDefault();
        }
    "
        class="relative">
        @if ($isLocked)
            <div
                style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); z-index: 999999; display: flex; align-items: center; justify-content: center; padding: 24px;">

                <div
                    class="bg-white rounded-xl shadow-xl p-4 text-center border border-gray-100 w-full max-w-xl transition-all scale-100">

                    <div class="mb-4 flex justify-center">

                        <img src="{{ asset('icons/shield.png') }}" class="w-20" alt="">

                    </div>

                    <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tighter mb-3">
                        Akses Terputus!
                    </h2>

                    <div class="space-y-4 mb-10">
                        <p class="text-md text-gray-600 font-medium leading-relaxed">
                            Sistem mendeteksi aktivitas di luar jendela ujian. Untuk menjaga integritas, sesi Anda telah
                            <span class="text-red-600 font-bold underline">DITANGGUHKAN</span> secara otomatis.
                        </p>

                        <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 flex gap-3 text-left">
                            <x-heroicon-m-information-circle class="w-5 h-5 text-amber-600 shrink-0" />
                            <p class="text-xs text-gray-500 font-medium leading-normal">
                                Pelanggaran dicatat oleh sistem (IP, Waktu, & Perangkat). Silahkan hubungi pengawas
                                ruangan jika ini adalah kendala teknis yang tidak disengaja.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <x-filament::button color="danger" size="xl" wire:click="backToDashboard"
                            class="w-full !rounded-2xl py-4 text-lg font-black uppercase tracking-wide shadow-xl shadow-red-100"
                            icon="heroicon-m-arrow-path">
                            Minta Token Baru
                        </x-filament::button>

                        <p class="text-[10px] text-gray-400 uppercase tracking-[0.4em] font-black mt-6">
                            Security Protocol — System ID: {{ auth()->id() }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div style="{{ $isLocked ? 'filter: blur(40px); pointer-events: none; user-select: none; opacity: 0.1;' : '' }}"
            class="transition-all duration-1000">
            @include('filament.student.pages.parts.exam-content')
        </div>
    </div>

    {{-- @script
        <script>
            // Logika deteksi tetap sama karena sudah optimal
            document.addEventListener('visibilitychange', function() {
                if (document.hidden && !$wire.isLocked) {
                    $wire.call('lockExam');
                }
            });

            window.addEventListener('blur', function() {
                if (!$wire.isLocked) {
                    $wire.call('lockExam');
                }
            });

            document.onkeydown = function(e) {
                // F12, Ctrl+Shift+I, Ctrl+U, Alt+Tab (sebagian), Meta key
                if (e.keyCode == 123 ||
                    (e.ctrlKey && e.shiftKey && e.keyCode == 73) ||
                    (e.ctrlKey && e.keyCode == 85) ||
                    e.metaKey) {
                    $wire.call('lockExam');
                    return false;
                }
            };
        </script>
    @endscript --}}
</x-filament-panels::page>
