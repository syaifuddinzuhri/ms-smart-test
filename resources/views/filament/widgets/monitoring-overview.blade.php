<x-filament-widgets::widget>

    <x-filament::section icon="heroicon-o-information-circle" icon-color="info">
        <x-slot name="heading">
            Panduan & Informasi Monitoring
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 py-2">
            {{-- Jeda Ujian --}}
            <div class="flex gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                    <x-heroicon-m-pause class="w-5 h-5 text-blue-600" />
                </div>
                <div>
                    <h4 class="text-sm font-bold text-gray-950">Jeda Ujian</h4>
                    <p class="text-xs text-gray-500 leading-relaxed">Menghentikan akses peserta
                        sementara. Sesi hanya bisa dilanjutkan setelah memasukkan token baru dari Admin.</p>
                </div>
            </div>

            {{-- Reset Ujian --}}
            <div class="flex gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                    <x-heroicon-m-trash class="w-5 h-5 text-red-600" />
                </div>
                <div>
                    <h4 class="text-sm font-bold text-gray-950">Reset Ujian</h4>
                    <p class="text-xs text-gray-500 leading-relaxed text-red-600 font-medium">⚠️
                        PERHATIAN: Akan menghapus seluruh jawaban peserta. Peserta harus mengulang dari awal.</p>
                </div>
            </div>

            {{-- Tambah Durasi --}}
            <div class="flex gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                    <x-heroicon-m-clock class="w-5 h-5 text-green-600" />
                </div>
                <div>
                    <h4 class="text-sm font-bold text-gray-950">Tambah Durasi</h4>
                    <p class="text-xs text-gray-500 leading-relaxed">Memberikan tambahan waktu kepada
                        peserta secara individu jika terjadi kendala teknis.</p>
                </div>
            </div>

            {{-- Paksa Selesai --}}
            <div class="flex gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                    <x-heroicon-m-stop-circle class="w-5 h-5 text-red-600" />
                </div>
                <div>
                    <h4 class="text-sm font-bold text-red-950">Paksa Selesai</h4>
                    <p class="text-xs text-red-500 leading-relaxed">Menghentikan ujian peserta
                        secara paksa dan langsung menghitung skor akhir saat ini.</p>
                </div>
            </div>
        </div>

        <div
            class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400 italic">
            <div class="flex items-center gap-2">
                <span class="relative flex h-2 w-2">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                Data diperbarui secara otomatis setiap 5 detik.
            </div>
            <div>Gunakan fitur filter untuk monitoring data sesuai kebutuhan.</div>
        </div>
    </x-filament::section>

    @if ($stats)
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4 mt-6">

            {{-- Card: Total Peserta --}}
            <div class="relative overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                <div class="flex items-center gap-x-4">
                    <div class="rounded-lg bg-gray-100 p-3">
                        <x-heroicon-o-users class="h-6 w-6 text-gray-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Peserta</p>
                        <h4 class="text-2xl font-bold tracking-tight text-gray-950">{{ $stats['total'] }}</h4>
                    </div>
                </div>
                {{-- Dekorasi Latar Belakang --}}
                <div class="absolute -right-2 -bottom-2 opacity-5">
                    <x-heroicon-o-users class="h-16 w-16 text-gray-950" />
                </div>
            </div>

            {{-- Card: Sedang Ujian (Active) --}}
            <div class="relative overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                <div class="flex items-center gap-x-4">
                    <div class="rounded-lg bg-orange-50 p-3">
                        <x-heroicon-o-play-circle class="h-6 w-6 text-orange-600" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-500">Sedang Ujian</p>
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-orange-500"></span>
                            </span>
                        </div>
                        <h4 class="text-2xl font-bold tracking-tight text-orange-600">{{ $stats['ongoing'] }}</h4>
                    </div>
                </div>
                <div class="absolute -right-2 -bottom-2 opacity-5 text-orange-600">
                    <x-heroicon-o-play-circle class="h-16 w-16" />
                </div>
            </div>

            {{-- Card: Sudah Selesai --}}
            <div class="relative overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                <div class="flex items-center gap-x-4">
                    <div class="rounded-lg bg-green-50 p-3">
                        <x-heroicon-o-check-badge class="h-6 w-6 text-green-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Sudah Selesai</p>
                        <h4 class="text-2xl font-bold tracking-tight text-green-600">{{ $stats['completed'] }}</h4>
                    </div>
                </div>
                <div class="absolute -right-2 -bottom-2 opacity-5 text-green-600">
                    <x-heroicon-o-check-badge class="h-16 w-16" />
                </div>
            </div>

            {{-- Card: Pelanggaran --}}
            <div class="relative overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                <div class="flex items-center gap-x-4">
                    <div class="rounded-lg bg-red-50 p-3">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Pelanggaran</p>
                        <h4 class="text-2xl font-bold tracking-tight text-red-600">{{ $stats['violation'] }}</h4>
                    </div>
                </div>
                <div class="absolute -right-2 -bottom-2 opacity-5 text-red-600">
                    <x-heroicon-o-exclamation-triangle class="h-16 w-16" />
                </div>
            </div>

        </div>
    @endif
</x-filament-widgets::widget>
