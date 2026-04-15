<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <x-filament::button color="gray" outlined icon="heroicon-m-arrow-left" tag="a"
                href="{{ route('filament.student.pages.result-test') }}">
                Kembali ke Riwayat
            </x-filament::button>
            <div class="text-right">
                <p class="text-xs text-gray-500 uppercase font-bold tracking-widest">Waktu Selesai</p>
                <p class="text-sm font-semibold text-gray-700">{{ $examData['waktu_selesai'] }}</p>
            </div>
        </div>

        <!-- Section Detail Tes (Ringkas) -->
        <div class="bg-white px-5 py-4 rounded-2xl border border-gray-100 shadow-sm">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Mapel -->
                <div class="flex items-center gap-2.5">
                    <x-heroicon-m-book-open class="w-5 h-5 text-gray-400" />
                    <div class="space-y-1">
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tight leading-none">Mapel</p>
                        <p class="text-sm font-semibold text-gray-700 leading-tight">{{ $examData['mapel'] ?? '-' }}</p>
                    </div>
                </div>

                <!-- Kategori -->
                <div class="flex items-center gap-2.5">
                    <x-heroicon-m-tag class="w-5 h-5 text-gray-400" />
                    <div class="space-y-1">
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tight leading-none">Kategori</p>
                        <p class="text-sm font-semibold text-gray-700 leading-tight">{{ $examData['kategori'] ?? '-' }}
                        </p>
                    </div>
                </div>

                <!-- Jadwal -->
                <div class="flex items-center gap-2.5">
                    <x-heroicon-m-calendar-days class="w-5 h-5 text-gray-400" />
                    <div class="space-y-1">
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tight leading-none">Jadwal</p>
                        <p class="text-sm font-semibold text-gray-700 leading-tight">{{ $examData['jadwal'] ?? '-' }}
                        </p>
                    </div>
                </div>

                <!-- Durasi -->
                <div class="flex items-center gap-2.5">
                    <x-heroicon-m-clock class="w-5 h-5 text-gray-400" />
                    <div class="space-y-1">
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tight leading-none">Durasi</p>
                        <p class="text-sm font-semibold text-gray-700 leading-tight">{{ $examData['durasi'] ?? '0' }}
                            Menit</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm text-center">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-tighter">Skor Akhir</p>
                <p class="text-4xl font-black text-primary-600">{{ $examData['skor'] }}</p>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm text-center">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-tighter">Total Soal</p>
                <p class="text-4xl font-black text-gray-700">{{ $examData['total_soal'] }}</p>
            </div>
            <div class="bg-green-50 p-6 rounded-2xl border border-green-100 text-center">
                <p class="text-xs font-bold text-green-600 uppercase tracking-tighter">Jawaban Benar</p>
                <p class="text-4xl font-black text-green-600">{{ $examData['benar'] }}</p>
            </div>
            <div class="bg-red-50 p-6 rounded-2xl border border-red-100 text-center">
                <p class="text-xs font-bold text-red-600 uppercase tracking-tighter">Jawaban Salah</p>
                <p class="text-4xl font-black text-red-600">{{ $examData['salah'] }}</p>
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
                        $item['is_correct'],
                    'border-red-100 shadow-[0_10px_30px_-15px_rgba(239,68,68,0.1)]' => !$item[
                        'is_correct'
                    ],
                ])>

                    <div @class([
                        'absolute top-0 left-0 px-6 py-2 rounded-br-2xl font-black text-sm tracking-tighter shadow-sm',
                        'bg-green-500 text-white' => $item['is_correct'],
                        'bg-red-500 text-white' => !$item['is_correct'],
                    ])>
                        SOAL #{{ $item['no'] }}
                    </div>

                    <div class="flex justify-between items-center my-4">
                        <div class="flex items-center gap-2">
                            <span @class([
                                'flex items-center gap-1.5 px-4 py-1.5 rounded-full text-[11px] font-black uppercase tracking-wider',
                                'bg-green-50 text-green-600' => $item['is_correct'], // Ubah jadi ghost style agar nomor lebih dominan
                                'bg-red-50 text-red-600' => !$item['is_correct'],
                            ])>
                                @if ($item['is_correct'])
                                    <x-heroicon-s-check-circle class="w-4 h-4" /> Benar
                                @else
                                    <x-heroicon-s-x-circle class="w-4 h-4" /> Salah
                                @endif
                            </span>
                        </div>
                        <span
                            class="text-[10px] font-black text-gray-400 bg-gray-100 px-3 py-1 rounded-lg uppercase tracking-widest">
                            {{ $item['tipe'] }}
                        </span>
                    </div>

                    <div class="text-gray-800 font-bold text-lg leading-relaxed mb-6">
                        {!! $item['pertanyaan'] !!}
                    </div>

                    <div class="space-y-4">
                        @if (in_array($item['tipe'], ['PG', 'Multiple Choice', 'Radio']))
                            <div class="grid gap-2">
                                @foreach ($item['options'] ?? [] as $key => $option)
                                    @php
                                        $isSelected = is_array($item['jawaban_siswa'])
                                            ? in_array($key, $item['jawaban_siswa'])
                                            : $item['jawaban_siswa'] === $key;
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
                                            {{ $item['tipe'] !== 'Radio' ? strtoupper($key) : '' }}
                                        </div>
                                        <span class="font-medium text-sm">{{ $option }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @elseif(in_array($item['tipe'], ['Essay', 'Short Answer']))
                            <div @class([
                                'p-5 rounded-2xl border-2',
                                'bg-green-50 border-green-100 text-green-800' => $item['is_correct'],
                                'bg-red-50 border-red-100 text-red-800' => !$item['is_correct'],
                            ])>
                                <p class="text-[10px] uppercase font-black opacity-50 mb-2 tracking-widest">Jawaban:</p>
                                <div class="font-medium text-sm leading-relaxed">
                                    {!! nl2br(e($item['jawaban_siswa'])) !!}
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-50 flex justify-end">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                            Poin Diperoleh: {{ $item['is_correct'] ? '5.00' : '0.00' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
