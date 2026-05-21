<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- BACKUP DATABASE --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 bg-green-50 border-b border-green-100">
                <div class="flex-shrink-0 bg-green-100 rounded-lg p-2">
                    <x-heroicon-o-circle-stack class="w-6 h-6 text-green-600" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-green-900">Backup Database</h3>
                    <p class="text-xs text-green-600 font-medium">Aman — tidak menghapus data</p>
                </div>
            </div>
            <div class="px-5 py-4">
                <p class="text-sm text-gray-600 leading-relaxed">
                    Unduh seluruh isi database dalam format <strong>.sql</strong>.
                    File backup dapat digunakan untuk restore data kapan saja.
                </p>
                <ul class="mt-3 space-y-1 text-xs text-gray-500">
                    <li class="flex items-center gap-1.5">
                        <x-heroicon-m-check-circle class="w-3.5 h-3.5 text-green-500 flex-shrink-0" />
                        Semua tabel dan data tersimpan
                    </li>
                    <li class="flex items-center gap-1.5">
                        <x-heroicon-m-check-circle class="w-3.5 h-3.5 text-green-500 flex-shrink-0" />
                        Format SQL standar (kompatibel MySQL/MariaDB)
                    </li>
                </ul>
                <div class="mt-4">
                    {{ $this->backupDatabaseAction }}
                </div>
            </div>
        </div>

        {{-- RESET UJIAN --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 bg-amber-50 border-b border-amber-100">
                <div class="flex-shrink-0 bg-amber-100 rounded-lg p-2">
                    <x-heroicon-o-computer-desktop class="w-6 h-6 text-amber-600" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-900">Reset Database Ujian</h3>
                    <p class="text-xs text-amber-600 font-medium">Permanen — tidak dapat dibatalkan</p>
                </div>
            </div>
            <div class="px-5 py-4">
                <p class="text-sm text-gray-600 leading-relaxed">
                    Hapus seluruh data ujian. Soal, peserta, dan kelas <strong>tidak terpengaruh</strong>.
                </p>
                <ul class="mt-3 space-y-1 text-xs text-gray-500">
                    <li class="flex items-center gap-1.5">
                        <x-heroicon-m-x-circle class="w-3.5 h-3.5 text-red-400 flex-shrink-0" />
                        Ujian, token, sesi & jawaban
                    </li>
                    <li class="flex items-center gap-1.5">
                        <x-heroicon-m-check-circle class="w-3.5 h-3.5 text-green-500 flex-shrink-0" />
                        Peserta, kelas, dan soal tetap aman
                    </li>
                </ul>
                <div class="mt-4">
                    {{ $this->resetUjianAction }}
                </div>
            </div>
        </div>

        {{-- RESET PESERTA --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 bg-red-50 border-b border-red-100">
                <div class="flex-shrink-0 bg-red-100 rounded-lg p-2">
                    <x-heroicon-o-users class="w-6 h-6 text-red-600" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-red-900">Reset Database Peserta</h3>
                    <p class="text-xs text-red-500 font-medium">Berbahaya — termasuk data ujian</p>
                </div>
            </div>
            <div class="px-5 py-4">
                <p class="text-sm text-gray-600 leading-relaxed">
                    Hapus seluruh akun peserta, kelas, dan semua data ujian terkait. Soal tetap aman.
                </p>
                <ul class="mt-3 space-y-1 text-xs text-gray-500">
                    <li class="flex items-center gap-1.5">
                        <x-heroicon-m-x-circle class="w-3.5 h-3.5 text-red-400 flex-shrink-0" />
                        Peserta, akun, kelas
                    </li>
                    <li class="flex items-center gap-1.5">
                        <x-heroicon-m-x-circle class="w-3.5 h-3.5 text-red-400 flex-shrink-0" />
                        Ujian, token, sesi & jawaban
                    </li>
                    <li class="flex items-center gap-1.5">
                        <x-heroicon-m-check-circle class="w-3.5 h-3.5 text-green-500 flex-shrink-0" />
                        Soal, topik, dan mata pelajaran tetap aman
                    </li>
                </ul>
                <div class="mt-4">
                    {{ $this->resetPesertaAction }}
                </div>
            </div>
        </div>

        {{-- RESET SOAL --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 bg-red-50 border-b border-red-100">
                <div class="flex-shrink-0 bg-red-100 rounded-lg p-2">
                    <x-heroicon-o-document-text class="w-6 h-6 text-red-600" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-red-900">Reset Database Soal</h3>
                    <p class="text-xs text-red-500 font-medium">Berbahaya — termasuk file lampiran</p>
                </div>
            </div>
            <div class="px-5 py-4">
                <p class="text-sm text-gray-600 leading-relaxed">
                    Hapus semua soal, opsi, dan file lampiran. Topik dan mata pelajaran <strong>tidak terhapus</strong>.
                </p>
                <ul class="mt-3 space-y-1 text-xs text-gray-500">
                    <li class="flex items-center gap-1.5">
                        <x-heroicon-m-x-circle class="w-3.5 h-3.5 text-red-400 flex-shrink-0" />
                        Soal, opsi, lampiran & file storage
                    </li>
                    <li class="flex items-center gap-1.5">
                        <x-heroicon-m-x-circle class="w-3.5 h-3.5 text-red-400 flex-shrink-0" />
                        Soal yang ditautkan di ujian & jawaban peserta
                    </li>
                    <li class="flex items-center gap-1.5">
                        <x-heroicon-m-check-circle class="w-3.5 h-3.5 text-green-500 flex-shrink-0" />
                        Topik & mata pelajaran tetap aman
                    </li>
                </ul>
                <div class="mt-4">
                    {{ $this->resetSoalAction }}
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
