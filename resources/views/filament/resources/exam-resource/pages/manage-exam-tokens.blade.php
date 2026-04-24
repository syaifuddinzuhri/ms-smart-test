<x-filament-panels::page>
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            {{-- Sisi Kiri: Judul & Target --}}
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-primary-50 rounded-xl">
                        <x-heroicon-m-academic-cap class="w-6 h-6 text-primary-600" />
                    </div>
                    <h2 class="text-xl font-black text-gray-800 leading-tight">
                        {{ $record->title }}
                    </h2>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500 font-medium ml-12">
                    <x-heroicon-m-users class="w-3.5 h-3.5" />
                    <span>Target:</span>
                    <span class="text-gray-700 italic">
                        {{ $record->classrooms->map(fn($c) => "{$c->name}-{$c->major?->code}")->implode(', ') ?: '-' }}
                    </span>
                </div>
            </div>

            {{-- Sisi Kanan: Jadwal & Status --}}
            <div
                class="flex flex-wrap items-center gap-4 md:gap-8 border-t md:border-t-0 md:border-l border-gray-100 pt-4 md:pt-0 md:pl-8">
                <div>
                    <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest block mb-1">Jadwal
                        Pelaksanaan</span>
                    @php
                        $schedule = format_exam_range($record->start_time, $record->end_time);
                    @endphp

                    <div class="flex flex-col gap-1 mt-2">
                        @if ($schedule['is_same_day'])
                            {{-- TAMPILAN HARI SAMA --}}
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex items-center gap-1.5 bg-gray-100 px-2 py-1 rounded-md border border-gray-200">
                                    <x-heroicon-m-calendar-days class="w-3.5 h-3.5 text-gray-500" />
                                    <span class="text-sm font-bold text-gray-700 whitespace-nowrap">
                                        {{ $schedule['date'] }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-1.5 text-primary-600 font-medium">
                                    <x-heroicon-m-clock class="w-3.5 h-3.5" />
                                    <span class="text-xs tracking-tight uppercase">
                                        {{ $schedule['time'] }}
                                    </span>
                                </div>
                            </div>
                        @else
                            {{-- TAMPILAN BEDA HARI --}}
                            <div class="flex items-center gap-2 text-[11px]">
                                <div class="flex flex-col">
                                    <span
                                        class="text-[9px] uppercase font-black text-gray-400 leading-none mb-1">Mulai</span>
                                    <span
                                        class="font-bold text-gray-700 bg-gray-50 px-2 py-0.5 rounded border">{{ $schedule['start'] }}</span>
                                </div>

                                <x-heroicon-m-arrow-long-right class="w-4 h-4 text-gray-300 mt-3" />

                                <div class="flex flex-col">
                                    <span
                                        class="text-[9px] uppercase font-black text-gray-400 leading-none mb-1">Selesai</span>
                                    <span
                                        class="font-bold text-gray-700 bg-gray-50 px-2 py-0.5 rounded border">{{ $schedule['end'] }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @php
                        // 1. Ambil durasi dasar (asumsi $record adalah ExamSession)
                        // Jika $record adalah Exam, gunakan $record->duration
                        $baseDuration = $record->duration ?? ($record->exam->duration ?? 0);

                        // 2. Hitung Tambahan (Sesuai Logika Sebelumnya)
                        $sessionLogs = collect($record->extension_log ?? []);
                        $examLogs = isset($record->exam) ? collect($record->exam->extension_log ?? []) : collect([]);

                        // Prioritas: Jika ada log individu pakai itu, jika tidak ada pakai log global (exam)
                        $additional = $sessionLogs->isNotEmpty()
                            ? $sessionLogs->sum('minutes')
                            : $examLogs->sum('minutes');
                    @endphp

                    <div class="text-center px-4 border-l border-gray-100">
                        <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest block mb-1">
                            Durasi
                        </span>
                        <x-filament::badge color="info" size="sm" icon="heroicon-m-clock">
                            {{ $baseDuration }}
                            @if ($additional > 0)
                                <span class="text-success-600 font-bold"> +{{ $additional }}</span>
                            @endif
                            Menit
                        </x-filament::badge>
                    </div>

                    <div class="text-center px-4 border-l border-gray-100">
                        <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest block mb-1">
                            Status
                        </span>
                        <x-filament::badge :color="$record->status->getColor()" size="sm">
                            {{ $record->status->getLabel() }}
                        </x-filament::badge>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- KOLOM KIRI: GENERATOR --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="p-4 bg-blue-50 border border-blue-100 rounded-2xl">
                <h4 class="text-xs font-black text-blue-800 uppercase tracking-widest mb-2 italic">Info Kadaluarsa:</h4>
                <p class="text-[10px] text-blue-700 leading-relaxed">
                    Token dihitung berdasarkan <b>Waktu Mulai Ujian</b>
                    ({{ $record->start_time->format('d F Y, H:i T') }}).
                    Jika Anda membuat token setelah ujian dimulai, maka dihitung dari <b>Waktu Sekarang</b>.
                </p>
            </div>

            <x-filament::section icon="heroicon-o-key" icon-color="primary">
                <x-slot name="heading">Generator Token</x-slot>
                <div class="space-y-6">
                    {{ $this->form }}
                    <x-filament::button wire:click="generateBatch" class="w-full" size="lg">
                        Buat Token
                    </x-filament::button>
                </div>
            </x-filament::section>

        </div>

        {{-- KOLOM KANAN: LIST TOKEN --}}
        <div class="lg:col-span-2" wire:poll.30s.keep-alive>
            <x-filament::section icon="heroicon-o-list-bullet" icon-color="success">
                <x-slot name="heading">Daftar Token Ujian</x-slot>

                <x-slot name="headerEnd">
                    <div class="flex items-center gap-3">
                        <div class="flex bg-gray-100 p-1 rounded-lg">
                            <button wire:click="setFilter(null)"
                                class="px-3 py-1 text-[10px] font-bold uppercase rounded-md transition {{ !$filterType ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500' }}">
                                Semua
                            </button>
                            <button wire:click="setFilter('access')"
                                class="px-3 py-1 text-[10px] font-bold uppercase rounded-md transition {{ $filterType === 'access' ? 'bg-white shadow-sm text-green-600' : 'text-gray-500' }}">
                                Awal Masuk
                            </button>
                            <button wire:click="setFilter('relogin')"
                                class="px-3 py-1 text-[10px] font-bold uppercase rounded-md transition {{ $filterType === 'relogin' ? 'bg-white shadow-sm text-red-600' : 'text-gray-500' }}">
                                Masuk Ulang
                            </button>
                        </div>

                        <x-filament::button wire:click="exportPdf" color="gray" icon="heroicon-m-document-arrow-down"
                            size="sm" variant="outline">
                            Export PDF
                        </x-filament::button>
                    </div>
                </x-slot>

                <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-full border border-gray-100 mb-4">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-success-500"></span>
                    </span>
                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-tight">
                        Realtime Auto-Sync <span class="text-gray-400 font-medium">(per 30 detik)</span>
                    </span>
                </div>

                <div class="overflow-x-auto border border-gray-100 rounded-xl">
                    <table class="w-full text-sm text-left">
                        <thead
                            class="bg-gray-50 text-gray-500 uppercase text-[10px] font-black tracking-widest text-center">
                            <tr>
                                <th class="px-4 py-3 text-left">Token</th>
                                <th class="px-4 py-3 text-left">Tipe</th>
                                <th class="px-4 py-3 italic">Digunakan</th>
                                <th class="px-4 py-3 italic">Berakhir</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($this->getTokensQuery()->get() as $token)
                                @php
                                    $isExpired = now()->greaterThan($token->expired_at);
                                    $isUsed = $token->is_single_use && $token->used_at !== null;
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition text-center">
                                    <td
                                        class="px-4 py-3 font-token text-xl font-black text-left {{ $isExpired || $isUsed ? 'text-gray-300 line-through' : 'text-primary-600' }}">
                                        {{ $token->token }}
                                    </td>
                                    <td class="px-4 py-3 text-left">
                                        <x-filament::badge :color="$token->type->getColor()">
                                            {{ $token->type->getLabel() }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-4 py-3 font-bold text-gray-600">
                                        {{ $token->used_count }} <span
                                            class="text-[9px] text-gray-400 uppercase font-medium">Peserta</span>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-[10px] text-gray-500 font-medium uppercase tracking-tighter">
                                        {{ $token->expired_at->format('d/m/Y H:i T') }}
                                        <span
                                            class="block text-[8px] opacity-60">({{ $token->expired_at->diffForHumans() }})</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($isUsed)
                                            <x-filament::badge color="danger">HANGUS</x-filament::badge>
                                        @elseif($isExpired)
                                            <x-filament::badge color="gray">EXPIRED</x-filament::badge>
                                        @else
                                            <x-filament::badge color="success">AKTIF</x-filament::badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <x-filament::icon-button icon="heroicon-m-trash" color="danger"
                                            wire:click="deleteToken('{{ $token->id }}')" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        Tidak ada token
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
