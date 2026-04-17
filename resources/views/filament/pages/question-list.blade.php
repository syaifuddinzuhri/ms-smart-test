<x-filament::page>
    {{-- FILTER --}}
    <div>
        {{ $this->form }}
    </div>

    @php
        $summary = $this->getSummary();
    @endphp

    {{-- SUMMARY --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <x-filament::card class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ $summary['pg'] }}</div>
            <div class="text-sm text-gray-500">Pilihan Ganda & Benar Salah</div>
        </x-filament::card>

        <x-filament::card class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $summary['short'] }}</div>
            <div class="text-sm text-gray-500">Jawaban Singkat</div>
        </x-filament::card>

        <x-filament::card class="text-center">
            <div class="text-2xl font-bold text-orange-600">{{ $summary['essay'] }}</div>
            <div class="text-sm text-gray-500">Essay</div>
        </x-filament::card>

        <x-filament::card class="text-center">
            <div class="text-2xl font-bold">{{ $summary['total'] }}</div>
            <div class="text-sm text-gray-500">Total Soal</div>
        </x-filament::card>
    </div>

    {{-- EMPTY STATE --}}
    @if (!$this->filters['subject_id'] || !$this->filters['question_category_id'])
        <x-filament::card class="text-center">
            <div class="text-gray-400 text-md">
                Silakan pilih Mata Pelajaran dan Topik terlebih dahulu
            </div>
        </x-filament::card>
    @else
        {{-- TABS (INTERAKTIF) --}}
        <div x-data="{ tab: 'pg' }">

            <div class="flex gap-2 justify-between mb-6">
                <div class="flex gap-2">
                    <button @click="tab='pg'" :class="tab === 'pg' ? 'bg-primary-600 text-white' : 'bg-gray-100'"
                        class="px-4 py-2 rounded-xl text-sm font-medium">
                        Pilihan Ganda & Benar Salah
                    </button>

                    <button @click="tab='short'" :class="tab === 'short' ? 'bg-blue-600 text-white' : 'bg-gray-100'"
                        class="px-4 py-2 rounded-xl text-sm font-medium">
                        Jawaban Singkat
                    </button>

                    <button @click="tab='essay'"
                        :class="tab === 'essay' ? 'bg-orange-600 text-white' : 'bg-gray-100'"
                        class="px-4 py-2 rounded-xl text-sm font-medium">
                        Essay
                    </button>
                </div>

                @if ($this->filters['subject_id'] && $this->filters['question_category_id'] && $summary['total'] > 0)
                    <div class="flex justify-end">
                        <button wire:click="mountAction('bulkDeleteQuestion')"
                            class="flex items-center gap-2 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white px-4 py-2 rounded-xl border border-red-200 transition-all duration-200 text-sm font-semibold shadow-sm">
                            <x-heroicon-m-trash class="w-4 h-4" />
                            Hapus Semua
                        </button>
                    </div>
                @endif
            </div>

            {{-- ========================
            PG
            ======================== --}}
            <div x-show="tab === 'pg'" class="space-y-4">
                @php $pgData = $this->getPgQuestions(); @endphp

                @forelse ($pgData as $q)
                    @php
                        // Hitung nomor urut berkelanjutan
                        $currentNumber = ($pgData->currentPage() - 1) * $pgData->perPage() + $loop->iteration;
                    @endphp

                    <div
                        class="relative bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden group">

                        {{-- NOMOR SOAL: Dibuat lebih slim --}}
                        <div class="absolute top-0 left-0 flex overflow-hidden rounded-br-xl">
                            <div
                                class="bg-green-600 group-hover:bg-green-700 transition-colors text-white px-3 py-1 shadow-sm">
                                <div class="flex items-center gap-1.5 leading-none">
                                    <span
                                        class="text-[9px] uppercase tracking-wider font-semibold opacity-80">Soal</span>
                                    <span class="text-sm font-black">{{ $currentNumber }}</span>
                                </div>
                            </div>

                            <div class="flex items-center bg-gray-50 border-gray-200 px-2 gap-2">
                                {{-- Edit Button --}}
                                <a href="{{ $this->getEditUrl($q->id) }}"
                                    class="text-gray-400 hover:text-blue-600 transition">
                                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                                </a>

                                {{-- Delete Button (Filament Action) --}}
                                <button wire:click="mountAction('deleteQuestion', { id: '{{ $q->id }}' })"
                                    class="text-gray-400 hover:text-red-600 transition">
                                    <x-heroicon-m-trash class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="p-4">
                            {{-- KONTEN PERTANYAAN: Ukuran text disesuaikan --}}
                            <div
                                class="prose max-w-none mt-6 text-gray-800 text-base font-medium leading-snug soal-content">
                                {!! $q->question_text !!}
                            </div>

                            @include('filament.components.question-media', ['question' => $q])

                            {{-- PILIHAN JAWABAN: Grid gap lebih rapat --}}
                            <div class="grid gap-2">
                                @foreach ($q->options as $opt)
                                    <div @class([
                                        'flex items-center justify-between border rounded-lg px-3 py-2 transition-all duration-200',
                                        'bg-emerald-50 border-emerald-400 ring-1 ring-emerald-400/10' =>
                                            $opt->is_correct,
                                        'bg-gray-50 border-gray-100 hover:border-gray-300 hover:bg-white' => !$opt->is_correct,
                                    ])>

                                        <div class="flex gap-3 items-center">
                                            {{-- Label Huruf (A, B, C): Ukuran diperkecil --}}
                                            <div @class([
                                                'w-7 h-7 flex items-center justify-center rounded-md text-xs font-bold shadow-xs transition-colors',
                                                'bg-emerald-500 text-white' => $opt->is_correct,
                                                'bg-white text-gray-400 border border-gray-200' => !$opt->is_correct,
                                            ])>
                                                {{ $opt->label }}
                                            </div>

                                            {{-- Teks Jawaban --}}
                                            <div dir="auto" @class([
                                                'prose max-w-none text-[13px] soal-content',
                                                'font-semibold text-emerald-900' => $opt->is_correct,
                                                'text-gray-600' => !$opt->is_correct,
                                            ])>
                                                {!! $opt->text !!}
                                            </div>
                                        </div>

                                        {{-- Status Benar: Lebih compact --}}
                                        @if ($opt->is_correct)
                                            <div
                                                class="flex items-center gap-1 bg-emerald-500 text-white px-2 py-1 rounded-md shadow-sm">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span
                                                    class="text-[9px] font-bold uppercase tracking-tighter">Kunci</span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                @empty
                    <x-filament::card class="text-center text-gray-400">
                        Tidak ada soal
                    </x-filament::card>
                @endforelse

                {{-- Render Pagination --}}
                <div class="mt-4">
                    {{ $pgData->links() }}
                </div>
            </div>

            {{-- ========================
                    SHORT ANSWER
                    ======================== --}}
            <div x-show="tab === 'short'" class="space-y-4">
                @php $shortData = $this->getShortQuestions(); @endphp

                @forelse ($shortData as $q)
                    @php
                        // Hitung nomor urut berkelanjutan
                        $currentNumber = ($shortData->currentPage() - 1) * $shortData->perPage() + $loop->iteration;
                    @endphp
                    <div
                        class="relative bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden group">

                        <div class="absolute top-0 left-0 flex overflow-hidden rounded-br-xl">
                            <div class="bg-blue-600 text-white px-3 py-1 shadow-sm">
                                <div class="flex items-center gap-1.5 leading-none">
                                    <span
                                        class="text-[9px] uppercase tracking-wider font-semibold opacity-80">Soal</span>
                                    <span class="text-sm font-black">{{ $currentNumber }}</span>
                                </div>
                            </div>
                            <div class="flex items-center bg-gray-50 border-gray-200 px-2 gap-2">
                                {{-- Edit Button --}}
                                <a href="{{ $this->getEditUrl($q->id) }}"
                                    class="text-gray-400 hover:text-blue-600 transition">
                                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                                </a>

                                {{-- Delete Button (Filament Action) --}}
                                <button wire:click="mountAction('deleteQuestion', { id: '{{ $q->id }}' })"
                                    class="text-gray-400 hover:text-red-600 transition">
                                    <x-heroicon-m-trash class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="p-4 pt-10">
                            {{-- PERTANYAAN --}}
                            <div class="prose max-w-none mt-6">
                                <div class="text-gray-800 text-base font-medium leading-snug soal-content"
                                    x-data="{}">
                                    {!! $q->question_text !!}
                                </div>
                            </div>

                            @include('filament.components.question-media', ['question' => $q])

                            {{-- BOX JAWABAN --}}
                            <div class="bg-blue-50/50 border border-blue-100 rounded-lg p-3 flex items-start gap-3">
                                <div class="bg-blue-600 text-white p-1 rounded">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase tracking-widest font-bold text-blue-600 mb-0.5">
                                        Kunci Jawaban</p>
                                    <p class="text-sm font-bold text-blue-900">{{ $q->correct_answer_text }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                @empty
                    <x-filament::card class="text-center text-gray-400">Tidak ada soal</x-filament::card>
                @endforelse
                {{-- Render Pagination --}}
                <div class="mt-4">
                    {{ $shortData->links() }}
                </div>
            </div>

            {{-- ========================
ESSAY
======================== --}}
            <div x-show="tab === 'essay'" class="space-y-4">
                @php $essayData = $this->getEssayQuestions(); @endphp

                @forelse ($this->getEssayQuestions() as $q)
                    @php
                        // Hitung nomor urut berkelanjutan
                        $currentNumber = ($essayData->currentPage() - 1) * $essayData->perPage() + $loop->iteration;
                    @endphp
                    <div
                        class="relative bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden group">

                        {{-- LABEL & NOMOR --}}
                        <div class="absolute top-0 left-0 flex overflow-hidden rounded-br-xl">
                            <div class="bg-orange-500 text-white px-3 py-1 shadow-sm">
                                <div class="flex items-center gap-1.5 leading-none">
                                    <span
                                        class="text-[9px] uppercase tracking-wider font-semibold opacity-80">Soal</span>
                                    <span class="text-sm font-black">{{ $currentNumber }}</span>
                                </div>
                            </div>
                            <div class="flex items-center bg-gray-50 border-gray-200 px-2 gap-2">
                                {{-- Edit Button --}}
                                <a href="{{ $this->getEditUrl($q->id) }}"
                                    class="text-gray-400 hover:text-blue-600 transition">
                                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                                </a>

                                {{-- Delete Button (Filament Action) --}}
                                <button wire:click="mountAction('deleteQuestion', { id: '{{ $q->id }}' })"
                                    class="text-gray-400 hover:text-red-600 transition">
                                    <x-heroicon-m-trash class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="p-4 pt-10">
                            {{-- PERTANYAAN --}}
                            <div class="prose max-w-none mt-6">
                                <div class="text-gray-800 text-base font-medium leading-snug soal-content"
                                    x-data="{}">
                                    {!! $q->question_text !!}
                                </div>
                            </div>

                            @include('filament.components.question-media', ['question' => $q])

                            {{-- INFO PENILAIAN --}}
                            <div
                                class="flex items-center gap-2 text-gray-400 border-t border-dashed border-gray-100 pt-3">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-[11px] font-medium tracking-wide italic">Penilaian manual oleh
                                    pengajar</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <x-filament::card class="text-center text-gray-400">Tidak ada soal</x-filament::card>
                @endforelse
                <div class="mt-4">
                    {{ $essayData->links() }}
                </div>
            </div>

        </div>

    @endif
</x-filament::page>
