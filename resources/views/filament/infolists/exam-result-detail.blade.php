@php
    $session = $getRecord();
    $user = $session->user;

    $exam = \App\Models\Exam::where('id', $session->exam_id)
        ->with(['category', 'subject'])
        ->withCount('examQuestions')
        ->first();

    $questions = $exam->questions()->get();
    $results = app(\App\Services\ExamService::class)->getQuestions($exam, $session, false);

    $submittedAnswersCount = \App\Models\ExamAnswer::where('exam_session_id', $session->id)->count();

    $totalSoal = count($results);
    $correctAnswers = collect($results)->where('is_correct', 1)->count();
    $wrongAnswers = collect($results)->where('is_correct', 0)->count();
    $pending = collect($results)->whereStrict('is_correct', null)->count();
    $unanswered = max(0, $totalSoal - $submittedAnswersCount);
@endphp


<div class="space-y-6">
    <!-- STICKY NAVIGASI RESPONSIF -->
    <div class="sticky top-[64px] -mx-4 sm:mx-0 z-20 transition-all duration-300">
        <div class="bg-white/80 backdrop-blur-xl border-b sm:border border-gray-200 sm:rounded-3xl shadow-lg p-3 sm:p-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">

                <!-- Label & Ringkasan Singkat (Hanya muncul di desktop atau mode compact) -->
                <div class="flex items-center gap-3 pr-4 border-r border-gray-200">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Navigasi</span>
                        <span class="text-xs font-bold text-gray-700">{{ $totalSoal }} Soal</span>
                    </div>
                </div>

                <!-- CONTAINER NOMOR (Scrollable di Mobile, Wrap di Desktop) -->
                <div class="flex-1 overflow-x-auto no-scrollbar py-1">
                    <div
                        class="flex sm:flex-wrap flex-nowrap gap-2 min-w-max sm:min-w-0 max-h-[100px] overflow-y-auto pr-4">
                        @foreach ($results as $navItem)
                            @php
                            @endphp
                            <a href="#question-{{ $navItem['number'] }}" @class([
                                'flex-shrink-0 w-9 h-9 sm:w-10 sm:h-10 flex items-center justify-center rounded-xl font-bold text-[11px] sm:text-xs transition-all border-2',
                                'bg-green-500 border-green-400 text-white shadow-sm shadow-green-100' =>
                                    $navItem['is_correct'] === 1,

                                'bg-red-500 border-red-400 text-white shadow-sm shadow-red-100' =>
                                    $navItem['is_correct'] === 0,

                                'bg-yellow-500 border-yellow-400 text-white shadow-sm shadow-yellow-100' =>
                                    is_null($navItem['is_correct']) && $navItem['has_answer'],

                                'bg-gray-100 border-gray-300 text-gray-400' =>
                                    is_null($navItem['is_correct']) && !$navItem['has_answer'],
                            ])>
                                {{ $navItem['number'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- STATISTIK RINGKAS (Muncul di Mobile & Desktop) -->
                <div class="flex items-center gap-3 pl-2 sm:pl-4 sm:border-l border-gray-200">
                    <div class="flex gap-2 text-[10px] font-bold uppercase">
                        <span class="text-green-600 bg-green-50 px-2 py-1 rounded-md">✔ {{ $correctAnswers }}</span>
                        <span class="text-red-600 bg-red-50 px-2 py-1 rounded-md">✘ {{ $wrongAnswers }}</span>
                        <span class="text-gray-600 bg-gray-50 px-2 py-1 rounded-md">!
                            {{ $unanswered }}</span>
                        @if ($pending > 0)
                            <span class="text-orange-600 bg-orange-50 px-2 py-1 rounded-md">? {{ $pending }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="space-y-6">
        @foreach ($results as $index => $item)
            <div id="question-{{ $item['number'] }}" @class([
                'p-6 pt-10 rounded-3xl border-2 transition-all bg-white relative overflow-hidden md:scroll-mt-[220px] scroll-mt-[240px]',
                'border-gray-200 shadow-[0_10px_30px_-15px_rgba(34,197,94,0.1)]' => !$item[
                    'has_answer'
                ],
                'border-green-100 shadow-[0_10px_30px_-15px_rgba(34,197,94,0.1)]' =>
                    $item['is_correct'] === 1 && $item['has_answer'],
                'border-red-100 shadow-[0_10px_30px_-15px_rgba(239,68,68,0.1)]' =>
                    $item['is_correct'] === 0 && $item['has_answer'],
                'border-orange-100 shadow-[0_10px_30px_-15px_rgba(239,68,68,0.1)]' =>
                    is_null($item['is_correct']) && $item['has_answer'],
            ])>

                <div @class([
                    'absolute top-0 left-0 px-6 py-2 rounded-br-2xl font-black text-sm tracking-tighter shadow-sm',
                    'bg-gray-500 text-white' => !$item['has_answer'],
                    'bg-green-500 text-white' =>
                        $item['is_correct'] === 1 && $item['has_answer'],
                    'bg-red-500 text-white' => $item['is_correct'] === 0 && $item['has_answer'],
                    'bg-yellow-500 text-white' =>
                        is_null($item['is_correct']) && $item['has_answer'],
                ])>
                    SOAL #{{ $index + 1 }}
                </div>
                <div @class([
                    'absolute top-0 right-0 px-4 py-2 md:px-5 md:py-3 rounded-bl-2xl bg-gray-100 text-center leading-none max-w-[220px] md:max-w-none',
                ])>
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        {{ $item['type_label'] }}
                    </span>
                </div>

                <div class="flex justify-between items-center my-4">
                    <div class="flex items-center gap-2">
                        <span @class([
                            'flex items-center gap-1.5 px-4 py-1.5 rounded-full text-[11px] font-black uppercase tracking-wider',
                            'bg-gray-50 text-gray-600' => !$item['has_answer'],
                            'bg-green-50 text-green-600' =>
                                $item['is_correct'] === 1 && $item['has_answer'],
                            'bg-red-50 text-red-600' =>
                                $item['is_correct'] === 0 && $item['has_answer'],
                            'bg-yellow-50 text-yellow-600' =>
                                is_null($item['is_correct']) && $item['has_answer'],
                        ])>
                            @if (!$item['has_answer'])
                                <x-heroicon-s-x-circle class="w-4 h-4" /> Tidak Dijawab
                            @else
                                @if (is_null($item['is_correct']))
                                    <x-heroicon-s-pencil class="w-4 h-4" /> Belum Dikoreksi
                                @elseif($item['is_correct'])
                                    <x-heroicon-s-check-circle class="w-4 h-4" /> Benar
                                @else
                                    <x-heroicon-s-x-circle class="w-4 h-4" /> Salah
                                @endif
                            @endif
                        </span>
                    </div>
                </div>

                <div class="prose max-w-none mt-6 text-gray-800 text-base font-medium leading-snug soal-content mb-4">
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
                            'bg-gray-50 border-gray-100 text-gray-800' => !$item['has_answer'],
                            'bg-green-50 border-green-100 text-green-800' =>
                                $item['is_correct'] === 1 && $item['has_answer'],
                            'bg-red-50 border-red-100 text-red-800' =>
                                $item['is_correct'] === 0 && $item['has_answer'],
                            'bg-yellow-50 border-yellow-100 text-yellow-800' =>
                                is_null($item['is_correct']) && $item['has_answer'],
                        ])>
                            <p class="text-[10px] uppercase font-black opacity-50 mb-2 tracking-widest">Jawaban:
                            </p>
                            <div
                                class="prose max-w-none mt-6 text-gray-800 text-base font-medium leading-snug soal-content">
                                {!! $item['answer'] !!}
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 pt-4 border-t border-gray-50 flex justify-end">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        Poin Diperoleh:
                        @if (!$item['has_answer'])
                            {{-- Logika untuk Jawaban Kosong (Ambil poin pinalti & tambahkan minus di UI) --}}
                            @php
                                $penalty = 0;
                                if ($item['is_pg']) {
                                    $penalty = $item['point_pg_null'];
                                } elseif ($item['is_short']) {
                                    $penalty = $item['point_short_answer_null'];
                                } elseif ($item['is_essay']) {
                                    $penalty = $item['point_essay_null'];
                                }
                            @endphp

                            {{-- Jika nilai penalty > 0, tambahkan minus di depan --}}
                            {{ $penalty > 0 ? '-' : '' }}{{ number_format($penalty, 2) }}
                        @elseif (is_null($item['is_correct']))
                            {{-- Jika ada jawaban tapi belum dikoreksi (Essay/Isian) --}}
                            Belum dikoreksi
                        @else
                            {{-- Jika sudah dikoreksi --}}
                            {{ $item['score'] }}
                        @endif
                    </span>
                </div>
            </div>
        @endforeach
    </div>
</div>


<style>
    /* Haluskan pergerakan scroll */
    html {
        scroll-behavior: smooth;
    }
</style>
