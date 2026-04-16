<x-filament-panels::page>
    <div wire:loading.flex wire:target="submit"
        class="fixed inset-0 z-[9999] items-center justify-center bg-gray-900/60 backdrop-blur-sm">
        <div class="bg-white p-8 rounded-xl shadow-2xl flex flex-col items-center gap-4 border border-gray-200">
            <x-filament::loading-indicator class="w-12 h-12 text-primary-600" />
            <div class="text-center">
                <p class="text-lg font-bold text-gray-900">Sedang Memproses Import...</p>
                <p class="text-sm text-gray-500">Mohon tunggu, sistem sedang memvalidasi dan menyimpan data soal.</p>
            </div>

            <div class="w-64 h-2 bg-gray-200 rounded-full overflow-hidden relative">
                <div class="absolute inset-0 bg-primary-600 animate-progress-indeterminate"></div>
            </div>
        </div>
    </div>

    <div>
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-amber-600">
                    <x-heroicon-m-exclamation-triangle class="w-5 h-5" />
                    <span>Penting Sebelum Import</span>
                </div>
            </x-slot>

            <ul class="space-y-2 text-sm text-gray-600 list-disc list-inside">
                <li>Gunakan <strong>Template Resmi</strong> yang dapat diunduh melalui tombol di pojok kanan atas.</li>
                <li>Untuk tipe Pilihan Ganda, pastikan opsi jawaban tidak kosong jika kunci jawaban merujuk ke opsi
                    tersebut.</li>
                <li>Sistem akan melakukan validasi otomatis, jika ada data yang tidak valid, proses import akan
                    dihentikan.</li>
                <li class="text-danger-600 font-medium italic">
                    <strong>Sistem Atomicity:</strong> Jika terdapat satu saja baris yang gagal, maka seluruh data dalam
                    file tersebut akan <strong>gagal di-import</strong> (Rollback) untuk menjaga integritas data.
                </li>
            </ul>
        </x-filament::section>
    </div>

    <form wire:submit.prevent="submit">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            {{-- 2. Perbarui Tombol dengan wire:loading --}}
            <x-filament::button type="submit" size="lg" icon="heroicon-m-check-circle"
                wire:loading.attr="disabled" wire:target="submit">
                {{-- Teks saat normal --}}
                <span wire:loading.remove wire:target="submit">
                    Mulai Import Data
                </span>

                {{-- Teks saat loading --}}
                <span wire:loading wire:target="submit">
                    Memproses...
                </span>
            </x-filament::button>
        </div>
    </form>

    {{-- Tampilkan Pesan Error Baris --}}
    @if (count($failures) > 0)
        <div class="mt-2">
            <x-filament::section icon="heroicon-o-exclamation-circle" icon-color="danger">
                <x-slot name="heading">Detail Kesalahan Baris Excel</x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead>
                            <tr class="border-b">
                                <th class="p-2 w-20">No. Soal</th>
                                <th class="p-2">Potongan Soal</th>
                                <th class="p-2 text-danger-600">Pesan Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($failures as $error)
                                <tr class="border-b bg-danger-50/20">
                                    <td class="p-2 text-center font-bold">{{ $error['no'] }}</td>
                                    <td class="p-2 text-gray-500">{{ $error['question'] ?? '-' }}</td>
                                    <td class="p-2 text-danger-700 font-medium">{{ $error['reason'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif

    <style>
        @keyframes progress-indeterminate {
            0% {
                left: -100%;
                width: 100%;
            }

            100% {
                left: 100%;
                width: 100%;
            }
        }

        .animate-progress-indeterminate {
            animation: progress-indeterminate 1.5s infinite linear;
        }
    </style>

</x-filament-panels::page>
