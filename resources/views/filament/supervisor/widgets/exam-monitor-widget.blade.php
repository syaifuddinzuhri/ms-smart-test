<x-filament-widgets::widget>
    <div wire:poll.10s>
        @php
            $sessions = $this->getData();
            $examId = data_get($filters, 'exam_id');
            $classroomId = data_get($filters, 'classroom_id');
            $ongoingCount = $sessions->where('status.value', 'ongoing')->count();
            $pauseCount = $sessions->where('status.value', 'pause')->count();
        @endphp

        @if (!$examId || !$classroomId)
            {{-- Bagian filter belum dipilih tetap sama --}}
            <div
                class="flex flex-col items-center justify-center p-12 bg-gray-50 dark:bg-gray-800 rounded-xl border border-dashed border-gray-300">
                <x-heroicon-o-funnel class="w-12 h-12 text-gray-400 mb-4" />
                <p class="text-gray-600 dark:text-gray-400 font-medium text-center">Silakan pilih Ujian dan Kelas.</p>
            </div>
        @else
            <div class="flex flex-col gap-6 mb-8">
                {{-- Header Section: Title & Action --}}
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div class="flex items-start gap-4">
                        {{-- Icon Dekoratif --}}
                        <div
                            class="hidden sm:flex p-3 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <x-heroicon-o-computer-desktop class="w-8 h-8 text-emerald-600" />
                        </div>

                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                {{-- Live Indicator Badge --}}
                                <span
                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 text-[10px] font-bold uppercase tracking-wider border border-orange-100 dark:border-orange-800">
                                    <span class="relative flex h-2 w-2">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-orange-500"></span>
                                    </span>
                                    Live Monitoring
                                </span>
                                <span class="text-gray-300 dark:text-gray-700">|</span>
                                <span class="text-[10px] font-medium text-gray-500 uppercase tracking-widest">Update
                                    per 10 detik</span>
                            </div>

                            <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight uppercase">
                                {{ $exam?->title ?? 'Pilih Ujian' }}
                            </h2>
                        </div>
                    </div>

                    {{-- Action Button --}}
                    <div class="flex items-center gap-2">
                        @if ($ongoingCount > 0)
                            {{ ($this->pauseAllAction)([]) }}
                        @endif
                        @if ($ongoingCount > 0 || $pauseCount > 0)
                            {{ ($this->resetAllAction)([]) }}
                        @endif
                    </div>
                </div>

                {{-- Stats Cards Section --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    {{-- Total Card --}}
                    <div
                        class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Peserta Aktif
                        </p>
                        <div class="flex items-end gap-2">
                            <span
                                class="text-2xl font-black text-gray-900 dark:text-white">{{ $sessions->count() }}</span>
                            <span class="text-xs text-gray-400 mb-1">Peserta</span>
                        </div>
                    </div>

                    {{-- Ongoing Card --}}
                    <div
                        class="bg-white dark:bg-gray-900 p-4 rounded-2xl border-l-4 border-l-orange-500 border-gray-100 dark:border-gray-800 shadow-sm">
                        <p class="text-xs font-medium text-orange-600 uppercase tracking-wider mb-1">Mengerjakan</p>
                        <div class="flex items-end gap-2">
                            <span class="text-2xl font-black text-gray-900 dark:text-white">{{ $ongoingCount }}</span>
                            <x-heroicon-s-play class="w-4 h-4 text-orange-500 mb-2 animate-pulse" />
                        </div>
                    </div>

                    {{-- Pause Card --}}
                    <div
                        class="bg-white dark:bg-gray-900 p-4 rounded-2xl border-l-4 border-l-blue-500 border-gray-100 dark:border-gray-800 shadow-sm">
                        <p class="text-xs font-medium text-blue-600 uppercase tracking-wider mb-1">Pause</p>
                        <div class="flex items-end gap-2">
                            <span class="text-2xl font-black text-gray-900 dark:text-white">
                                {{ $sessions->where('status.value', \App\Enums\ExamSessionStatus::PAUSE->value)->count() }}
                            </span>
                            <x-heroicon-s-pause class="w-4 h-4 text-blue-500 mb-2" />
                        </div>
                    </div>

                    {{-- Completed Card --}}
                    <div
                        class="bg-white dark:bg-gray-900 p-4 rounded-2xl border-l-4 border-l-emerald-500 border-gray-100 dark:border-gray-800 shadow-sm">
                        <p class="text-xs font-medium text-emerald-600 uppercase tracking-wider mb-1">Selesai</p>
                        <div class="flex items-end gap-2">
                            <span class="text-2xl font-black text-gray-900 dark:text-white">
                                {{ $sessions->where('status.value', \App\Enums\ExamSessionStatus::COMPLETED->value)->count() }}
                            </span>
                            <x-heroicon-s-check-badge class="w-4 h-4 text-emerald-500 mb-2" />
                        </div>
                    </div>
                </div>
            </div>

            @if ($sessions->isEmpty())
                <div class="text-center p-12 bg-white dark:bg-gray-800 rounded-xl border">
                    <p class="text-gray-500">Belum ada data pengerjaan.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($sessions as $session)
                        <div
                            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm flex flex-col justify-between transition">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span @class([
                                        'px-2 py-0.5 text-[10px] font-bold uppercase rounded-full',
                                        'bg-orange-100 text-orange-700 hover:border-orange-300' => $session->status->value === 'ongoing',
                                        'bg-green-100 text-green-700 hover:border-green-300' => $session->status->value === 'completed',
                                        'bg-blue-100 text-blue-700 hover:border-blue-300' => $session->status->value === 'pause',
                                        'bg-yellow-100 text-yellow-700 hover:border-yellow-300' => $session->status->value === 'pending',
                                    ])>
                                        {{ $session->status->getLabel() }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 italic">
                                        {{ $session->last_activity?->diffForHumans() }}
                                    </span>
                                </div>

                                <h3 class="font-bold text-gray-900 dark:text-white truncate">{{ $session->user->name }}
                                </h3>
                                <p class="text-xs text-gray-400 mb-4">{{ $session->user->student->nisn ?? '-' }}</p>

                                <div class="space-y-1 mb-4">
                                    <div class="flex justify-between text-[10px] font-medium text-gray-400">
                                        <span>PROGRES</span>
                                        <span>{{ $session->answers_count }} /
                                            {{ $session->exam->exam_questions_count }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2 dark:bg-gray-800 overflow-hidden">
                                        @php
                                            $totalSoal = max(1, $session->exam->exam_questions_count);
                                            $percent = ($session->answers_count / $totalSoal) * 100;
                                            $barColor =
                                                $session->status->value === 'completed'
                                                    ? 'bg-green-500'
                                                    : 'bg-orange-500';
                                        @endphp
                                        <div class="{{ $barColor }} h-2 rounded-full transition-all duration-700"
                                            style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2 flex gap-2">
                                @if ($session->status->value === 'ongoing')
                                    {{ ($this->pauseIndividualAction)(['id' => $session->id]) }}
                                @endif
                                @if ($session->status->value !== 'completed')
                                    {{ ($this->resetIndividualAction)(['id' => $session->id]) }}
                                @elseif($session->status->value === 'completed')
                                    <div
                                        class="flex-1 flex items-center justify-center text-green-600 text-[10px] font-bold gap-1 uppercase bg-green-50 rounded-lg py-1.5">
                                        <x-heroicon-m-check-badge class="w-4 h-4" />
                                        Ujian Selesai
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
    <x-filament-actions::modals />
</x-filament-widgets::widget>
