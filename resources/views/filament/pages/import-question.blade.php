<x-filament-panels::page>
    {{-- Loading Overlay --}}
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

    {{-- Tabs Logic menggunakan Alpine.js --}}
    <div x-data="{ activeTab: 'petunjuk' }">
        <x-filament::tabs>
            <x-filament::tabs.item alpine-active="activeTab === 'petunjuk'" x-on:click="activeTab = 'petunjuk'"
                icon="heroicon-m-information-circle">
                Petunjuk Import
            </x-filament::tabs.item>

            <x-filament::tabs.item alpine-active="activeTab === 'upload'" x-on:click="activeTab = 'upload'"
                icon="heroicon-m-cloud-arrow-up">
                Upload File
            </x-filament::tabs.item>
        </x-filament::tabs>

        <div class="mt-6">
            {{-- TAB 1: PETUNJUK --}}
            <div x-show="activeTab === 'petunjuk'" x-cloak class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Section 1: Petunjuk Umum --}}
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center gap-2 text-amber-600">
                                <x-heroicon-m-exclamation-triangle class="w-5 h-5" />
                                <span>Penting Sebelum Import</span>
                            </div>
                        </x-slot>

                        <ul class="space-y-2 text-sm text-gray-600 list-disc list-inside">
                            <li>Gunakan <strong>Template Resmi</strong> dari tombol pojok kanan atas.</li>
                            <li>Sistem menggunakan <strong>Atomicity</strong>: 1 baris salah, semua gagal import
                                (Rollback).</li>
                            <li>Pastikan file <code>.docx</code> tidak dalam keadaan terproteksi atau terkunci.</li>
                            <li>Jika menggunakan lampiran, bungkus file soal dan dokumen dalam satu <b>ZIP</b>.</li>
                            <li>Nama file <b>wajib</b> mengikuti nomor urut soal (Contoh: <code
                                    class="bg-gray-100 px-1">soal-1.png</code>).</li>
                            <li>Format didukung: <b>PNG, JPG, GIF, MP3, MP4, WAV, WEBM</b>.</li>
                            <li>Satu soal hanya bisa untuk 1 lampiran.</li>
                        </ul>

                        <div class="mt-2 p-2 bg-gray-50 border border-gray-200 rounded-lg">
                            <p class="font-bold text-sm uppercase">Contoh Isi ZIP:</p>
                            <code class="text-sm block whitespace-pre-line">
                                📂 soal.zip
                                ├── soal.docx / soal.xlsx (Nama file harus ini)
                                ├── soal-1.png (Gambar)
                                ├── soal-2.mp3 (Audio Listening)
                                └── soal-3.mp4 (Video Pendek)
                            </code>
                        </div>
                    </x-filament::section>

                    {{-- Section 2: Format Word --}}
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center gap-2 text-primary-600">
                                <x-heroicon-m-document-text class="w-5 h-5" />
                                <span>Panduan Format Native Word</span>
                            </div>
                        </x-slot>

                        <div class="space-y-4">
                            {{-- Alert Wajib Word --}}
                            <div class="p-3 bg-amber-50 border-l-4 border-amber-400 text-amber-800 text-sm">
                                <div class="flex items-center gap-2 font-bold mb-1">
                                    <x-heroicon-m-information-circle class="w-4 h-4" />
                                    <span>Wajib Menggunakan Microsoft Word</span>
                                </div>
                                Untuk hasil terbaik pada rumus matematika dan teks Arab, dilarang mengetik simbol
                                manual. Gunakan
                                fitur bawaan MS Word agar sistem dapat mengonversi data secara akurat.
                            </div>

                            <div class="space-y-3">
                                {{-- Instruksi Matematika --}}
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <p class="text-sm font-bold text-primary-600 uppercase mb-2">1. Matematika (Fitur
                                        Equation)
                                    </p>
                                    <ul class="text-sm text-gray-600 space-y-2">
                                        <li class="flex items-start gap-2">
                                            <span class="text-primary-500 font-bold">•</span>
                                            <span>Klik menu <strong>Insert > Equation</strong> untuk setiap
                                                rumus.</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="text-primary-500 font-bold">•</span>
                                            <span>Hindari mengetik pangkat (^) atau pecahan (/) secara manual di teks
                                                biasa.</span>
                                        </li>
                                    </ul>
                                    <div
                                        class="mt-2 p-2 bg-white rounded border flex flex-col gap-4 border-gray-200 font-mono text-sm text-center italic">
                                        <div>
                                            Contoh: <span class="text-blue-600"> [Objek Equation] </span> &rarr;
                                            $\frac{-b \pm
                                            \sqrt{D}}{2a}$
                                        </div>
                                        <div class="flex justify-center">
                                            Hasil:
                                            <div class="text-[16px]" x-data="{}" x-init="renderMathInElement($el, {
                                                delimiters: [
                                                    { left: '$$', right: '$$', display: true },
                                                    { left: '$', right: '$', display: false },
                                                    { left: '\\(', right: '\\)', display: false },
                                                    { left: '\\[', right: '\\]', display: true }
                                                ],
                                                throwOnError: false
                                            })">
                                                $\frac{-b \pm
                                                \sqrt{D}}{2a}$</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Instruksi Arab --}}
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <p class="text-sm font-bold text-emerald-600 uppercase mb-2">2. Teks Arab (Native
                                        Font)</p>
                                    <ul class="text-sm text-gray-600 space-y-2">
                                        <li class="flex items-start gap-2">
                                            <span class="text-emerald-500 font-bold">•</span>
                                            <span>Gunakan keyboard Arabic dan font standar (Amiri/Traditional
                                                Arabic).</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="text-emerald-500 font-bold">•</span>
                                            <span>Sistem otomatis mengatur posisi kanan-ke-kiri (RTL) saat soal
                                                ditampilkan.</span>
                                        </li>
                                    </ul>
                                    <div class="mt-2 p-2 bg-white rounded border border-gray-200 text-right font-arabic"
                                        dir="rtl">
                                        <span class="text-gray-600">مَنْ جَدَّ وَجَدَ</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Footer Note --}}
                            <p class="text-sm text-gray-400 italic">
                                * Sistem akan mengonversi objek Word di atas menjadi format digital yang mendukung
                                tampilan mobile &
                                desktop secara otomatis.
                            </p>
                        </div>
                    </x-filament::section>
                </div>
            </div>

            {{-- TAB 2: UPLOAD --}}
            <div x-show="activeTab === 'upload'" x-cloak>
                <form wire:submit.prevent="submit">
                    <x-filament::section>
                        {{ $this->form }}

                        <div class="mt-6 flex justify-end">
                            <x-filament::button type="submit" size="lg" icon="heroicon-m-check-circle"
                                wire:loading.attr="disabled" wire:target="submit">
                                <span wire:loading.remove wire:target="submit">Mulai Import Data</span>
                                <span wire:loading wire:target="submit">Memproses...</span>
                            </x-filament::button>
                        </div>
                    </x-filament::section>
                </form>

                {{-- Tampilkan Pesan Error Baris jika ada --}}
                @if (count($failures) > 0)
                    <div class="mt-6">
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
            </div>
        </div>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }

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
