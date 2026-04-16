<x-filament-panels::page>
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
            <x-filament::button type="submit" size="lg" icon="heroicon-m-check-circle">
                Mulai Import Data
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
                                <th class="p-2 w-20">Baris</th>
                                <th class="p-2">Potongan Soal</th>
                                <th class="p-2 text-danger-600">Pesan Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($failures as $error)
                                <tr class="border-b bg-danger-50/20">
                                    <td class="p-2 font-mono font-bold">{{ $error['row'] }}</td>
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

</x-filament-panels::page>
