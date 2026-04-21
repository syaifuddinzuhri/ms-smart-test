<x-filament-panels::page>
    {{-- 1. WELCOME HERO --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-green-600 to-green-700 p-8 shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h2 class="text-3xl font-black text-white tracking-tight">Halo, {{ auth()->user()->name }}! 👋</h2>
                <p class="mt-2 text-green-100 max-w-lg leading-relaxed">
                    Sistem <b>MS SMART TEST</b> siap digunakan. Pantau aktivitas peserta secara realtime dan pastikan integritas
                    ujian tetap terjaga.
                </p>
            </div>
            <div class="flex gap-3">
                <div class="bg-white/10 w-full backdrop-blur-md p-4 rounded-2xl border border-white/20 text-center">
                    <span class="block text-2xl font-black text-white">{{ $this->getStats()['ongoing_sessions'] }}</span>
                    <span class="text-[10px] uppercase font-bold text-green-200 tracking-wider">Sedang Ujian</span>
                </div>
            </div>
        </div>
        {{-- Dekorasi --}}
        <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
    </div>

    @php $stats = $this->getStats(); @endphp

    {{-- 2. MAIN STATS GRID --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {{-- Peserta --}}
        <div
            class="group flex relative overflow-hidden items-center gap-5 rounded-2xl bg-white p-6 border border-gray-100 shadow-sm transition-all hover:shadow-md hover:-translate-y-1">
            <div
                class="rounded-xl bg-blue-50 p-4 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                <x-heroicon-m-users class="h-8 w-8" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Peserta</p>
                <h4 class="text-3xl font-black text-gray-800 leading-none mt-1">
                    {{ number_format($stats['total_participants']) }}</h4>
            </div>

            <div
                class="absolute -right-4 -bottom-4 opacity-5 transition-transform duration-500 group-hover:scale-110 text-blue-900">
                <x-heroicon-m-users class="h-24 w-24" />
            </div>
        </div>

        {{-- Bank Soal --}}
        <div
            class="group flex items-center overflow-hidden relative gap-5 rounded-2xl bg-white p-6 border border-gray-100 shadow-sm transition-all hover:shadow-md hover:-translate-y-1">
            <div
                class="rounded-xl bg-amber-50 p-4 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                <x-heroicon-m-book-open class="h-8 w-8" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Bank Soal</p>
                <h4 class="text-3xl font-black text-gray-800 leading-none mt-1">
                    {{ number_format($stats['total_questions']) }}</h4>
            </div>
            <div
                class="absolute -right-4 -bottom-4 opacity-5 transition-transform duration-500 group-hover:scale-110 text-orange-900">
                <x-heroicon-m-book-open class="h-24 w-24" />
            </div>
        </div>

        {{-- Ujian Berlangsung --}}
        <div
            class="group flex items-center overflow-hidden relative gap-5 rounded-2xl bg-white p-6 border border-gray-100 shadow-sm transition-all hover:shadow-md hover:-translate-y-1">
            <div
                class="rounded-xl bg-emerald-50 p-4 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                <x-heroicon-m-clipboard-document-check class="h-8 w-8" />
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Ujian Aktif</p>
                <h4 class="text-3xl font-black text-gray-800 leading-none mt-1">{{ $stats['exam_active'] }}</h4>
            </div>
            <div
                class="absolute -right-4 -bottom-4 opacity-5 transition-transform duration-500 group-hover:scale-110 text-green-900">
                <x-heroicon-m-clipboard-document-check class="h-24 w-24" />
            </div>
        </div>
    </div>

    {{-- 3. EXAM STATUS BREAKDOWN --}}
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
        <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 mb-6 flex items-center gap-2">
            <span class="w-2 h-2 bg-primary-600 rounded-full"></span> Ringkasan Status Ujian
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Status: Draft --}}
            <div class="flex flex-col p-4 rounded-2xl bg-gray-50 border border-gray-100">
                <div class="flex justify-between items-center mb-2">
                    <span
                        class="px-3 py-1 rounded-full bg-gray-200 text-[10px] font-black uppercase text-gray-600">Draft</span>
                    <x-heroicon-m-pencil-square class="w-5 h-5 text-gray-400" />
                </div>
                <div class="text-2xl font-black text-gray-700">{{ $stats['exam_draft'] }}</div>
                <p class="text-[11px] text-gray-500 mt-1 italic">* Belum dipublikasi ke peserta</p>
            </div>

            {{-- Status: Active --}}
            <div class="flex flex-col p-4 rounded-2xl bg-green-50 border border-green-100">
                <div class="flex justify-between items-center mb-2">
                    <span
                        class="px-3 py-1 rounded-full bg-green-200 text-[10px] font-black uppercase text-green-700">Active</span>
                    <x-heroicon-m-play-circle class="w-5 h-5 text-green-500" />
                </div>
                <div class="text-2xl font-black text-green-700">{{ $stats['exam_active'] }}</div>
                <p class="text-[11px] text-green-600 mt-1 italic">* Sedang berlangsung/siap dikerjakan</p>
            </div>

            {{-- Status: Closed --}}
            <div class="flex flex-col p-4 rounded-2xl bg-blue-50 border border-blue-100">
                <div class="flex justify-between items-center mb-2">
                    <span
                        class="px-3 py-1 rounded-full bg-blue-200 text-[10px] font-black uppercase text-blue-700">Closed</span>
                    <x-heroicon-m-lock-closed class="w-5 h-5 text-blue-500" />
                </div>
                <div class="text-2xl font-black text-blue-700">{{ $stats['exam_closed'] }}</div>
                <p class="text-[11px] text-blue-600 mt-1 italic">* Masa ujian telah berakhir</p>
            </div>
        </div>
    </div>

    {{-- 4. RULES & GUIDELINES --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        <div class="bg-white rounded-3xl p-8 shadow-sm shadow-gray-200 relative overflow-hidden">
            <h3 class="text-lg font-black mb-6 flex items-center gap-3">
                <x-heroicon-o-shield-check class="w-6 h-6 text-primary-600" />
                Sistem Keamanan Ujian (Rules)
            </h3>
            <div class="space-y-6">
                <div class="flex gap-4">
                    <div
                        class="h-8 w-8 rounded-full bg-green-500/20 text-green-800 flex items-center justify-center shrink-0 font-bold text-xs">
                        1</div>
                    <div>
                        <p class="font-bold text-sm">Anti-Cheat Tab Detection</p>
                        <p class="text-xs text-gray-400 mt-1 leading-relaxed">Sistem akan mencatat log aktivitas jika
                            peserta mencoba berpindah tab atau membuka aplikasi lain selama ujian berlangsung.</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div
                        class="h-8 w-8 rounded-full bg-green-500/20 text-green-800 flex items-center justify-center shrink-0 font-bold text-xs">
                        2</div>
                    <div>
                        <p class="font-bold text-sm">Auto-Submit Mechanism</p>
                        <p class="text-xs text-gray-400 mt-1 leading-relaxed">Saat waktu (duration) habis, sistem akan
                            memaksa simpan jawaban terakhir peserta dan menutup sesi secara otomatis.</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div
                        class="h-8 w-8 rounded-full bg-green-500/20 text-green-800 flex items-center justify-center shrink-0 font-bold text-xs">
                        3</div>
                    <div>
                        <p class="font-bold text-sm">Single Session Lock</p>
                        <p class="text-xs text-gray-400 mt-1 leading-relaxed">Sistem mendukung satu akun peserta hanya
                            dapat digunakan
                            pada satu perangkat di waktu yang sama untuk mencegah kecurangan.</p>
                    </div>
                </div>
            </div>
            {{-- Dekorasi background --}}
            <x-heroicon-o-lock-closed class="absolute -right-10 -bottom-10 h-40 w-40 text-white/5" />
        </div>

        <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
            <h3 class="text-lg font-black text-gray-800 mb-6 flex items-center gap-3">
                <x-heroicon-o-light-bulb class="w-6 h-6 text-amber-500" />
                Alur Kerja Admin
            </h3>
            <div class="relative">
                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-100"></div>
                <div class="space-y-8 relative">
                    <div class="flex gap-6 items-start">
                        <div
                            class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold text-xs z-10 shrink-0">
                            1</div>
                        <div>
                            <p class="text-sm font-bold">Penyusunan Bank Soal</p>
                            <p class="text-xs text-gray-500 mt-1">Gunakan fitur Import (Word/Excel) agar lebih cepat.
                                Dukungan LaTeX & Arab tersedia.</p>
                        </div>
                    </div>
                    <div class="flex gap-6 items-start">
                        <div
                            class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold text-xs z-10 shrink-0">
                            2</div>
                        <div>
                            <p class="text-sm font-bold">Setting Parameter Ujian</p>
                            <p class="text-xs text-gray-500 mt-1">Tentukan durasi, poin, dan jadwal ujian. Atur status
                                ke <strong>Draft</strong> selama persiapan.</p>
                        </div>
                    </div>
                    <div class="flex gap-6 items-start">
                        <div
                            class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold text-xs z-10 shrink-0">
                            3</div>
                        <div>
                            <p class="text-sm font-bold">Publikasi & Monitoring</p>
                            <p class="text-xs text-gray-500 mt-1">Ubah status ke <strong>Active</strong> dan pantau
                                peserta di dashboard monitoring realtime.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</x-filament-panels::page>
