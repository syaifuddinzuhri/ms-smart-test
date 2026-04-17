<x-filament-panels::page>
    {{-- Loading Overlay --}}
    <div wire:loading.flex wire:target="previewImport, saveImport"
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

    <div class="grid grid-cols-1 gap-6">

        <!-- Kolom Atas: Panduan & Batasan -->
        <div class="col-span-1 grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-filament::section icon="heroicon-o-information-circle" icon-color="primary">
                <x-slot name="heading">Petunjuk Import</x-slot>
                <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex gap-3">
                        <div
                            class="flex-none w-6 h-6 rounded-full bg-green-50 text-green-600 flex items-center justify-center font-bold text-xs">
                            1</div>
                        <p>Download template Excel di pojok kanan atas dan isi data sesuai kolom.</p>
                    </div>
                    <div class="flex gap-3">
                        <div
                            class="flex-none w-6 h-6 rounded-full bg-green-50 text-green-600 flex items-center justify-center font-bold text-xs">
                            2</div>
                        <p>Username wajib <b>kecil, tanpa spasi & simbol</b>. Password otomatis menggunakan <b>NISN</b>.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <div
                            class="flex-none w-6 h-6 rounded-full bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs">
                            3</div>
                        <div class="text-danger-600 font-medium itadivc">
                            <strong>Sistem Atomicity:</strong> Jika terdapat satu saja baris yang gagal, maka seluruh
                            data dalam
                            file tersebut akan <strong>gagal di-import</strong> (Rollback) untuk menjaga integritas
                            data.
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="warning">
                <x-slot name="heading">Batasan Import</x-slot>
                <div class="space-y-2">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Untuk stabilitas sistem, terdapat batasan
                        sebagai berikut:</p>
                    <ul class="text-sm list-disc list-inside text-gray-600 dark:text-gray-400">
                        <li>Maksimal <b>500 Baris</b> per sekali upload.</li>
                        <li>Pastikan <b>Kode Kelas</b> sudah terdaftar di sistem.</li>
                        <li>Gunakan format tanggal <b>YYYY-MM-DD</b>.</li>
                    </ul>
                </div>
            </x-filament::section>
        </div>

        <!-- Bagian Form Upload -->
        <div class="col-span-1 space-y-6">
            <x-filament::section>
                <form wire:submit.prevent="previewImport" class="space-y-6">
                    {{ $this->form }}

                    <div class="flex items-center gap-3">
                        @if (empty($importData))
                            <x-filament::button type="submit" icon="heroicon-m-magnifying-glass"
                                wire:loading.attr="disabled" wire:target="data.file_import">
                                <span>
                                    Preview Data Excel
                                </span>
                            </x-filament::button>
                        @endif
                    </div>
                </form>
            </x-filament::section>

            <!-- Bagian Preview Tabel -->
            @if (count($importData) > 0)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <span>Preview Data</span>
                            <x-filament::badge color="info">{{ count($importData) }} Baris
                                ditemukan</x-filament::badge>
                        </div>
                    </x-slot>

                    <div class="overflow-x-auto border rounded-xl dark:border-gray-700">
                        <!-- Bagian Tabel Preview -->
                        <table class="w-full text-sm text-left">
                            <thead
                                class="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-300 uppercase text-[10px] font-bold tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-center">No</th>
                                    <th class="px-4 py-3">Nama Lengkap</th>
                                    <th class="px-4 py-3">Username & NISN</th>
                                    <th class="px-4 py-3">Status / Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                @foreach ($this->getPaginatedData() as $index => $item)
                                    @php $hasError = !empty($item['errors']); @endphp
                                    <tr
                                        class="{{ $hasError ? 'bg-red-50/50 dark:bg-red-900/10' : 'hover:bg-gray-50' }}">
                                        <td class="px-4 py-3 text-center text-gray-400">
                                            {{ ($currentPage - 1) * $perPage + $loop->iteration }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $item['name'] }}
                                            </div>
                                            <div class="text-[10px] text-gray-500 uppercase">{{ $item['class_code'] }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 space-y-1">
                                            <div class="text-xs"><span>{{ $item['username'] }}</span>
                                            </div>
                                            <div class="text-xs"><span>{{ $item['nisn'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($hasError)
                                                <div class="space-y-1">
                                                    @foreach ($item['errors'] as $err)
                                                        <div
                                                            class="flex items-center gap-1 text-red-600 dark:text-red-400 text-[10px] font-bold">
                                                            <x-heroicon-m-x-circle class="w-3 h-3" />
                                                            {{ $err }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <x-filament::badge color="success" size="sm"
                                                    icon="heroicon-m-check-circle">
                                                    Valid
                                                </x-filament::badge>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- KONTROL PAGINATION -->
                    <div class="mt-4 flex items-center justify-between bg-gray-50 dark:bg-white/5 p-2 rounded-lg">
                        <span class="text-xs text-gray-500">
                            Halaman {{ $currentPage }} dari {{ $this->getTotalPages() }} (Total
                            {{ count($importData) }} data)
                        </span>
                        <div class="flex gap-2">
                            <x-filament::button size="xs" color="gray" variant="outline"
                                wire:click="previousPage" :disabled="$currentPage === 1">
                                Sebelumnya
                            </x-filament::button>
                            <x-filament::button size="xs" color="gray" variant="outline" wire:click="nextPage"
                                :disabled="$currentPage >= $this->getTotalPages()">
                                Selanjutnya
                            </x-filament::button>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t dark:border-gray-700 flex items-center justify-end gap-3">
                        <div class="flex items-center justify-end gap-3">
                            {{-- Tombol di Footer: Muncul hanya saat preview ada --}}
                            <x-filament::button wire:click="resetImport" color="gray" variant="outline"
                                icon="heroicon-m-x-mark">
                                Batalkan & Hapus File
                            </x-filament::button>

                            <x-filament::button wire:click="saveImport" color="success"
                                icon="heroicon-m-arrow-down-tray" wire:loading.attr="disabled" wire:target="saveImport">
                                <span wire:loading.remove wire:target="saveImport">Simpan Semua Data</span>
                                <span wire:loading wire:target="saveImport">Menyimpan...</span>
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-panels::page>
