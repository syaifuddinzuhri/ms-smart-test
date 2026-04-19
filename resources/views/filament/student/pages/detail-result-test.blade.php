<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between items-start gap-y-8">
            <x-filament::button color="gray" outlined icon="heroicon-m-arrow-left" tag="a"
                href="{{ route('filament.student.pages.result-test') }}">
                Kembali ke Riwayat
            </x-filament::button>
            <div class="text-center md:text-right w-full md:w-auto">
                <p class="text-xs text-gray-500 uppercase font-bold tracking-widest">Waktu Selesai</p>
                <p class="text-sm font-semibold text-gray-700">{{ $session->finished_at->format('d/m/Y H:i:s T') }}</p>
            </div>
        </div>

        <div class="space-y-4">
            {{-- Metadata Section --}}
            <div class="bg-white px-5 py-4 rounded-2xl border border-gray-100 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Subject -->
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-gray-50 rounded-lg">
                            <x-heroicon-m-book-open class="w-5 h-5 text-gray-400" />
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tight leading-none">Mata
                                Pelajaran
                            </p>
                            <p class="text-sm font-semibold text-gray-700 leading-tight">{{ $exam->subject->name }}</p>
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-gray-50 rounded-lg">
                            <x-heroicon-m-tag class="w-5 h-5 text-gray-400" />
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tight leading-none">Kategori
                            </p>
                            <p class="text-sm font-semibold text-gray-700 leading-tight">{{ $exam->category->name }}</p>
                        </div>
                    </div>

                    <!-- Class & Major -->
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-gray-50 rounded-lg">
                            <x-heroicon-m-calendar-days class="w-5 h-5 text-gray-400" />
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tight leading-none">Kelas &
                                Jurusan</p>
                            <p class="text-sm font-semibold text-gray-700 leading-tight">{{ $stats['classroom'] }}</p>
                        </div>
                    </div>

                    <!-- Duration -->
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-gray-50 rounded-lg">
                            <x-heroicon-m-clock class="w-5 h-5 text-gray-400" />
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tight leading-none">Durasi
                                Pengerjaan
                            </p>
                            <p class="text-sm font-semibold text-gray-700 leading-tight">{{ $stats['actual_duration'] }} Menit
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 1: Primary Score & Summary --}}
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm text-center md:col-span-1">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-tighter mb-1">Total Skor Akhir</p>
                    @if ($exam->show_result_to_student)
                        <p class="text-5xl font-black text-primary-600">{{ $stats['score'] }}</p>

                        <p class="text-[10px] text-gray-400 mt-4 font-medium uppercase tracking-widest">
                            @if (is_null($stats['passing_grade']))
                                Tidak Ada Batas Minimum Skor
                            @else
                                KKM / Minimal Skor: {{ $stats['passing_grade'] }}
                            @endif
                        </p>
                    @else
                        <div class="py-2">
                            <div class="mb-3 flex justify-center">
                                <div class="p-3 bg-orange-50 rounded-2xl border border-orange-100">
                                    <x-heroicon-m-lock-closed class="w-8 h-8 text-orange-500" />
                                </div>
                            </div>

                            <p class="text-2xl font-black text-gray-800 leading-tight">Skor Akhir Dirahasiakan</p>
                            <p class="text-[11px] text-gray-400 font-medium mt-1 leading-relaxed px-4">
                                Hasil ujian ini saat ini disembunyikan oleh admin.
                            </p>

                            <div class="mt-5">
                                <div
                                    class="flex items-center gap-1.5 px-4 py-1.5 bg-gray-50 text-gray-500 rounded-full border border-gray-200 w-fit mx-auto">
                                    <x-heroicon-m-eye-slash class="w-3.5 h-3.5" />
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest text-gray-500">Menunggu
                                        Pengumuman</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div
                    class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm text-center flex flex-col justify-center">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-tighter mb-1">Jumlah Soal</p>
                    <p class="text-5xl font-black text-gray-700 leading-none mb-6">{{ $stats['total_questions'] }}</p>

                    {{-- Detail Tipe Soal --}}
                    <div class="flex items-center justify-center gap-3">
                        <div class="px-3 py-1 bg-gray-50 rounded-lg border border-gray-100">
                            <p class="text-[9px] font-bold text-gray-400 uppercase leading-none mb-1">Pilihan Ganda</p>
                            <p class="text-sm font-bold text-gray-700">{{ $stats['count_pg'] }}</p>
                        </div>
                        <div class="px-3 py-1 bg-gray-50 rounded-lg border border-gray-100">
                            <p class="text-[9px] font-bold text-gray-400 uppercase leading-none mb-1">Jawaban Singkat
                            </p>
                            <p class="text-sm font-bold text-gray-700">{{ $stats['count_short'] }}</p>
                        </div>
                        <div class="px-3 py-1 bg-gray-50 rounded-lg border border-gray-100">
                            <p class="text-[9px] font-bold text-gray-400 uppercase leading-none mb-1">Essay</p>
                            <p class="text-sm font-bold text-gray-700">{{ $stats['count_essay'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 2: Grading Details --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    class="bg-gray-50 p-4 md:p-6 rounded-2xl border border-gray-200 text-center flex flex-col justify-evenly">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-tighter mb-1">Soal Tidak Dijawab
                    </p>
                    <p class="text-3xl font-black text-gray-600">{{ $stats['unanswered'] }}</p>
                </div>
                <div
                    class="bg-green-50 p-4 md:p-6 rounded-2xl border border-green-100 text-center flex flex-col justify-evenly">
                    <p class="text-xs font-bold text-green-600 uppercase tracking-tighter mb-1">Soal Benar</p>
                    <p class="text-3xl font-black text-green-600">{{ $stats['correct_answers'] }}</p>
                </div>

                <div
                    class="bg-red-50 p-4 md:p-6 rounded-2xl border border-red-100 text-center flex flex-col justify-evenly">
                    <p class="text-xs font-bold text-red-600 uppercase tracking-tighter mb-1">Soal Salah</p>
                    <p class="text-3xl font-black text-red-600">{{ $stats['wrong_answers'] }}</p>
                </div>

                <div
                    class="bg-orange-50 p-4 md:p-6 rounded-2xl border border-orange-100 text-center flex flex-col justify-evenly">
                    <p class="text-xs font-bold text-orange-600 uppercase tracking-tighter mb-1">Soal Belum Dikoreksi
                    </p>
                    <p class="text-3xl font-black text-orange-600">{{ $stats['pending_review'] }}</p>
                    <p class="text-[10px] text-orange-400 leading-none mt-1 italic">*Menuggu hasil koreksi manual
                        (Essay)
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2 px-1">
                <x-heroicon-o-check-badge class="w-6 h-6 text-primary-500" />
                Analisis Pengerjaan
            </h3>

            @foreach ($results as $item)
                <div @class([
                    'p-6 pt-10 rounded-3xl border-2 transition-all bg-white relative overflow-hidden', // Tambah pt-10 untuk ruang nomor
                    'border-green-100 shadow-[0_10px_30px_-15px_rgba(34,197,94,0.1)]' =>
                        $item['is_correct'] === 1,
                    'border-red-100 shadow-[0_10px_30px_-15px_rgba(239,68,68,0.1)]' =>
                        $item['is_correct'] === 0,
                    'border-orange-100 shadow-[0_10px_30px_-15px_rgba(239,68,68,0.1)]' => is_null(
                        $item['is_correct']),
                ])>

                    <div @class([
                        'absolute top-0 left-0 px-6 py-2 rounded-br-2xl font-black text-sm tracking-tighter shadow-sm',
                        'bg-green-500 text-white' => $item['is_correct'] === 1,
                        'bg-red-500 text-white' => $item['is_correct'] === 0,
                        'bg-orange-500 text-white' => is_null($item['is_correct']),
                    ])>
                        SOAL #{{ $item['number'] }}
                    </div>

                    <div class="flex justify-between items-center my-4">
                        <div class="flex items-center gap-2">
                            <span @class([
                                'flex items-center gap-1.5 px-4 py-1.5 rounded-full text-[11px] font-black uppercase tracking-wider',
                                'bg-green-50 text-green-600' => $item['is_correct'] === 1,
                                'bg-red-50 text-red-600' => $item['is_correct'] === 0,
                                'bg-orange-50 text-orange-600' => is_null($item['is_correct']),
                            ])>
                                @if (is_null($item['is_correct']))
                                    <x-heroicon-s-pencil class="w-4 h-4" /> Belum Dikoreksi
                                @elseif($item['is_correct'])
                                    <x-heroicon-s-check-circle class="w-4 h-4" /> Benar
                                @else
                                    <x-heroicon-s-x-circle class="w-4 h-4" /> Salah
                                @endif
                            </span>
                        </div>
                        <span
                            class="text-[10px] font-black text-gray-400 bg-gray-100 px-3 py-1 rounded-lg uppercase tracking-widest">
                            {{ $item['type_label'] }}
                        </span>
                    </div>

                    <div
                        class="prose max-w-none mt-6 text-gray-800 text-base font-medium leading-snug soal-content mb-4">
                        {!! $item['question'] !!}
                    </div>

                    <div class="space-y-4">
                        @if ($item['is_pg'])
                            <div class="grid gap-2">
                                @foreach ($item['options'] ?? [] as $key => $option)
                                    @php
                                        $isSelected = is_array($item['answer'])
                                            ? in_array($key, $item['answer'])
                                            : $item['answer'] === $key;
                                    @endphp

                                    <div @class([
                                        'flex items-center gap-3 p-4 rounded-2xl border-2 transition-all',
                                        'bg-gray-50 border-gray-100 text-gray-500 opacity-60' => !$isSelected,
                                        'bg-green-50 border-green-200 text-green-700 ring-2 ring-green-100' =>
                                            $isSelected && $item['is_correct'],
                                        'bg-red-50 border-red-200 text-red-700 ring-2 ring-red-100' =>
                                            $isSelected && !$item['is_correct'],
                                    ])>
                                        <div @class([
                                            'w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black border-2',
                                            'bg-white border-gray-200' => !$isSelected,
                                            'bg-green-500 border-green-500 text-white' =>
                                                $isSelected && $item['is_correct'],
                                            'bg-red-500 border-red-500 text-white' =>
                                                $isSelected && !$item['is_correct'],
                                        ])>
                                            {{ $item['is_multiple'] ? strtoupper($key) : '' }}
                                        </div>
                                        <div
                                            class="prose max-w-none text-gray-800 text-base font-medium leading-snug soal-content">
                                            {!! $option !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($item['is_short'] || $item['is_essay'])
                            <div @class([
                                'p-5 rounded-2xl border-2',
                                'bg-green-50 border-green-100 text-green-800' => $item['is_correct'] === 1,
                                'bg-red-50 border-red-100 text-red-800' => $item['is_correct'] === 0,
                                'bg-orange-50 border-orange-100 text-orange-800' => is_null(
                                    $item['is_correct']),
                            ])>
                                <p class="text-[10px] uppercase font-black opacity-50 mb-2 tracking-widest">Jawaban:</p>
                                <div
                                    class="prose max-w-none mt-6 text-gray-800 text-base font-medium leading-snug soal-content">
                                    {!! $item['answer'] !!}
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($exam->show_result_to_student)
                        <div class="mt-6 pt-4 border-t border-gray-50 flex justify-end">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                Poin Diperoleh: {{ is_null($item['is_correct']) ? 'Belum dikoreksi' : $item['score'] }}
                            </span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
