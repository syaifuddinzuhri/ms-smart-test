<x-filament-panels::page>
    @php
        $isForbidden = $record->status !== \App\Enums\ExamStatus::DRAFT;
    @endphp
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
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
                    <div class="flex flex-col gap-1 mt-2">
                        @php
                            $schedule = format_exam_range($record->start_time, $record->end_time);
                        @endphp

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
                    <div class="text-center px-4 border-l border-gray-100">
                        <span
                            class="text-[10px] font-black uppercase text-gray-400 tracking-widest block mb-1">Durasi</span>
                        <x-filament::badge color="info" size="sm" icon="heroicon-m-clock">
                            {{ $record->duration }} Menit
                        </x-filament::badge>
                    </div>
                    <div class="text-center px-4 border-l border-gray-100">
                        <span
                            class="text-[10px] font-black uppercase text-gray-400 tracking-widest block mb-1">Status</span>
                        <x-filament::badge :color="$record->status->getColor()" size="sm">
                            {{ $record->status->getLabel() }}
                        </x-filament::badge>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">

        {{-- BAGIAN ATAS: FILTER & REVIEW --}}
        @if (!$isForbidden)
            <x-filament::section icon="heroicon-o-magnifying-glass" icon-color="primary">
                <x-slot name="heading">Filter Bank Soal</x-slot>

                <div class="space-y-6">
                    {{ $this->form }}

                    @php $summary = $this->getAvailableSummary(); @endphp

                    @if ($summary)
                        <div class="mt-4 p-6 bg-gray-50/50 border border-gray-200 rounded-3xl">
                            <!-- Header Ringkasan -->
                            <div class="flex items-center gap-2 mb-6">
                                <div class="bg-primary-100 rounded-lg">
                                    <x-heroicon-m-magnifying-glass-circle class="w-6 h-6 text-primary-600" />
                                </div>
                                <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-500">
                                    Hasil Penelusuran Bank Soal
                                </h4>
                            </div>

                            <div class="flex flex-col lg:flex-row items-center gap-8">
                                <!-- Grid Statistik -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 w-full">

                                    <!-- Total Soal (Highlight) -->
                                    <div
                                        class="relative overflow-hidden bg-white p-4 rounded-2xl border border-gray-200 shadow-sm transition-all hover:shadow-md">
                                        <div class="absolute top-0 right-0 p-2 opacity-10">
                                            <x-heroicon-s-circle-stack class="w-12 h-12 text-primary-600" />
                                        </div>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total
                                            Soal
                                        </p>
                                        <h3 class="text-3xl font-black text-gray-900 mt-1">{{ $summary['total'] }}</h3>
                                        <div class="w-8 h-1 bg-primary-500 mt-2 rounded-full"></div>
                                    </div>

                                    <!-- PG & Benar Salah -->
                                    <div
                                        class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                            <p
                                                class="text-[9px] font-bold text-gray-500 uppercase tracking-tighter leading-none">
                                                Pilihan Ganda & Benar Salah</p>
                                        </div>
                                        <div>
                                            <h3 class="text-2xl font-black text-gray-800">{{ $summary['pg'] }}</h3>
                                            <p class="text-[10px] text-gray-400 font-medium">Butir Soal</p>
                                        </div>
                                    </div>

                                    <!-- Jawaban Singkat -->
                                    <div
                                        class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                            <p
                                                class="text-[9px] font-bold text-gray-500 uppercase tracking-tighter leading-none">
                                                Jawaban Singkat</p>
                                        </div>
                                        <div>
                                            <h3 class="text-2xl font-black text-gray-800">{{ $summary['short'] }}</h3>
                                            <p class="text-[10px] text-gray-400 font-medium">Butir Soal</p>
                                        </div>
                                    </div>

                                    <!-- Essay -->
                                    <div
                                        class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                                            <p
                                                class="text-[9px] font-bold text-gray-500 uppercase tracking-tighter leading-none">
                                                Essay / Uraian</p>
                                        </div>
                                        <div>
                                            <h3 class="text-2xl font-black text-gray-800">{{ $summary['essay'] }}</h3>
                                            <p class="text-[10px] text-gray-400 font-medium">Butir Soal</p>
                                        </div>
                                    </div>

                                </div>

                                <!-- Tombol Aksi (Tampil Jika Ada Soal) -->
                                @if ($summary['total'] > 0)
                                    <div class="shrink-0 w-full lg:w-auto">
                                        <x-filament::button wire:click="addQuestions" size="xl"
                                            icon="heroicon-m-plus-circle"
                                            class="w-full shadow-lg shadow-primary-500/20 py-4">
                                            Masukkan ke Ujian
                                        </x-filament::button>
                                        <p class="text-center text-[10px] text-gray-400 mt-2 italic font-medium">
                                            * Soal duplikat akan otomatis diabaikan
                                        </p>
                                    </div>
                                @else
                                    <div
                                        class="shrink-0 flex items-center gap-3 px-6 py-4 bg-gray-100 border border-gray-200 rounded-2xl shadow-inner">
                                        <x-heroicon-m-minus-circle class="w-5 h-5 text-gray-400" />
                                        <div class="flex flex-col">
                                            <p
                                                class="text-xs font-black text-gray-500 uppercase tracking-tight leading-none">
                                                Tidak ada soal baru
                                            </p>
                                            <p class="text-[9px] text-gray-400 mt-1 font-medium">
                                                Bank soal kosong atau semua soal sudah terpilih.
                                            </p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif

        {{-- BAGIAN BAWAH: DAFTAR SOAL TERPILIH --}}
        <x-filament::section icon="heroicon-o-check-badge" icon-color="success">
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <span>Soal Terpilih untuk Ujian Ini</span>
                </div>
            </x-slot>

            <x-slot name="headerEnd">
                @if ($record->questions()->count() > 0 && !$isForbidden)
                    <x-filament::button wire:click="mountAction('removeAll')" color="danger" icon="heroicon-m-trash"
                        size="sm" variant="outline">
                        Kosongkan Semua Soal
                    </x-filament::button>
                @endif
            </x-slot>

            <div class="space-y-4">
                <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 border border-blue-100 rounded-full w-fit">
                    <x-heroicon-m-arrows-up-down class="w-3.5 h-3.5 text-blue-600" />
                    <p class="text-[9px] font-bold text-blue-700 uppercase tracking-tighter">
                        Urutan Default: Pilihan Ganda & Benar Salah <span class="mx-1 text-blue-300">&rarr;</span>
                        Jawaban
                        Singkat <span class="mx-1 text-blue-300">&rarr;</span> Essay
                    </p>
                </div>

                @php
                    // Hitung ringkasan dari soal yang sudah masuk ke ujian (record)
                    $selectedSummary = [
                        'total' => $record->questions()->count(),
                        'pg' => $record
                            ->questions()
                            ->whereIn('question_type', [
                                \App\Enums\QuestionType::SINGLE_CHOICE,
                                \App\Enums\QuestionType::MULTIPLE_CHOICE,
                                \App\Enums\QuestionType::TRUE_FALSE,
                            ])
                            ->count(),
                        'short' => $record
                            ->questions()
                            ->where('question_type', \App\Enums\QuestionType::SHORT_ANSWER)
                            ->count(),
                        'essay' => $record
                            ->questions()
                            ->where('question_type', \App\Enums\QuestionType::ESSAY)
                            ->count(),
                    ];
                @endphp

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="p-3 bg-white border border-gray-200 rounded-2xl shadow-sm flex items-center gap-3">
                        <div class="p-2 bg-gray-100 rounded-lg text-gray-600"><x-heroicon-s-circle-stack
                                class="w-4 h-4" /></div>
                        <div>
                            <p class="text-[9px] font-bold text-gray-400 uppercase leading-none">Total</p>
                            <p class="text-lg font-black text-gray-800">{{ $selectedSummary['total'] }}</p>
                        </div>
                    </div>
                    <div
                        class="p-3 bg-white border border-gray-200 rounded-2xl shadow-sm flex items-center gap-3 border-l-4 border-l-emerald-500">
                        <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600"><x-heroicon-m-check-circle
                                class="w-4 h-4" /></div>
                        <div>
                            <p class="text-[9px] font-bold text-gray-400 uppercase leading-none">Pilihan Ganda & Benar
                                Salah</p>
                            <p class="text-lg font-black text-emerald-600">{{ $selectedSummary['pg'] }}</p>
                        </div>
                    </div>
                    <div
                        class="p-3 bg-white border border-gray-200 rounded-2xl shadow-sm flex items-center gap-3 border-l-4 border-l-blue-500">
                        <div class="p-2 bg-blue-50 rounded-lg text-blue-600"><x-heroicon-m-pencil class="w-4 h-4" />
                        </div>
                        <div>
                            <p class="text-[9px] font-bold text-gray-400 uppercase leading-none">Jawaban Singkat</p>
                            <p class="text-lg font-black text-blue-600">{{ $selectedSummary['short'] }}</p>
                        </div>
                    </div>
                    <div
                        class="p-3 bg-white border border-gray-200 rounded-2xl shadow-sm flex items-center gap-3 border-l-4 border-l-amber-500">
                        <div class="p-2 bg-amber-50 rounded-lg text-amber-600"><x-heroicon-m-document-text
                                class="w-4 h-4" /></div>
                        <div>
                            <p class="text-[9px] font-bold text-gray-400 uppercase leading-none">Essay</p>
                            <p class="text-lg font-black text-amber-600">{{ $selectedSummary['essay'] }}</p>
                        </div>
                    </div>
                </div>

                @php $selectedQuestions = $this->getExamQuestions(); @endphp

                @if ($selectedQuestions->count() > 0)
                    <div class="overflow-x-auto border border-gray-100 rounded-xl">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] font-black tracking-widest">
                                <tr>
                                    <th class="px-4 py-3 w-10 text-center">No</th>
                                    <th class="px-4 py-3">Isi Soal</th>
                                    <th class="px-4 py-3 w-50">Tipe</th>
                                    @if (!$isForbidden)
                                        <th class="px-4 py-3 w-10">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($selectedQuestions as $index => $question)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3 text-center font-bold text-gray-400">
                                            {{ $question->order }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">
                                            <div
                                                class="prose max-w-none text-gray-800 text-base font-medium leading-snug soal-content">
                                                {!! $question->question_text !!}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-xs uppercase font-bold text-gray-500">
                                            {{ $question->question_type->getLabel() }}
                                        </td>
                                        @if (!$isForbidden)
                                            <td class="px-4 py-3 text-center">
                                                <x-filament::icon-button icon="heroicon-m-x-circle" color="danger"
                                                    tooltip="Hapus"
                                                    wire:click="mountAction('removeQuestion', { question_id: '{{ $question->question_id }}' })" />
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $selectedQuestions->links() }}
                    </div>
                @else
                    <div class="py-12 text-center text-gray-400 italic">Belum ada soal yang dipilih.</div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
