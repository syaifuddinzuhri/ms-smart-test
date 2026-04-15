<div class="space-y-6">
    <div
        class="flex flex-col md:flex-row gap-4 justify-between items-center bg-white p-2 rounded-xl shadow-sm border border-gray-200">
        <div class="flex bg-gray-100 p-1 rounded-xl w-full">
            <button wire:click="setTab('pg')" @class([
                'flex-1 md:w-40 py-2 text-sm font-bold rounded-lg transition-all',
                'bg-white shadow text-primary-600' => $activeTab === 'pg',
                'text-gray-500 hover:text-gray-700' => $activeTab !== 'pg',
            ])>
                Pilihan Ganda ({{ $totalPG }})
            </button>
            <button wire:click="setTab('essay')" @class([
                'flex-1 md:w-40 py-2 text-sm font-bold rounded-lg transition-all',
                'bg-white shadow text-primary-600' => $activeTab === 'essay',
                'text-gray-500 hover:text-gray-700' => $activeTab !== 'essay',
            ])>
                Essay / Isian ({{ $totalEssay }})
            </button>
        </div>

        <div x-data="timerHandler(@js($durationInSeconds))" x-init="initTimer()">
            <div class="px-6 py-2 bg-red-50 border border-red-100 rounded-xl">
                <span class="text-red-600 font-mono font-black text-xl" x-text="formatTime(remaining)">00:00:00</span>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-2 min-h-[200px] relative shadow-sm">
        <div class="flex md:flex-row flex-col md:justify-between md:items-center border-b border-gray-100 p-4">
            <span class="text-xs font-black uppercase tracking-[0.2em] text-gray-400">
                Soal Nomor {{ $currentStep }}
                ({{ $activeTab === 'pg' ? 'Pilihan Ganda' : 'Essay' }})
            </span>
            <span class="text-xs font-bold text-primary-600 bg-primary-50 py-1 rounded-full">
                Selesai: {{ count(array_filter($data)) }} / {{ $totalPG + $totalEssay }}
            </span>
        </div>

        <div class="px-4 py-2">
            {{ $this->form }}
        </div>
    </div>

    <!-- Navigasi & Tombol Ragu-ragu -->
    <div
        class="flex flex-col md:flex-row items-center justify-between gap-4 bg-gray-50 p-4 rounded-xl border border-gray-200">
        <div class="flex flex-wrap md:flex-nowrap gap-2 gap-y-4 w-full justify-center md:justify-start">

            <!-- TOMBOL SEBELUMNYA -->
            <x-filament::button color="gray" outlined wire:click="previous" icon="heroicon-m-arrow-left"
                :disabled="$activeTab === 'pg' && $currentStep === 1" class="flex-1 md:flex-none order-2 md:order-1">
                Sebelumnya
            </x-filament::button>

            <!-- TOMBOL RAGU-RAGU -->
            @php
                $currentId = $activeTab === 'pg' ? $currentStep : $currentStep + $totalPG;
            @endphp
            <x-filament::button tag="button" wire:click="toggleDoubt('q{{ $currentId }}')" color="warning"
                :outlined="!in_array('q' . $currentId, $doubtfulQuestions)" class="w-full md:w-auto order-1 md:order-2">
                Ragu-Ragu
            </x-filament::button>

            <!-- TOMBOL SELANJUTNYA -->
            <x-filament::button color="primary" wire:click="next" icon-position="after" icon="heroicon-m-arrow-right"
                :disabled="$activeTab === 'essay' && $currentStep === $totalEssay" class="flex-1 md:flex-none order-3 md:order-3">
                Selanjutnya
            </x-filament::button>

        </div>

        <div class="w-full flex justify-center md:justify-end">
            {{ $this->submitAction }}
        </div>
    </div>

    <!-- GRID NOMOR SOAL -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 flex items-center gap-2">
            <x-heroicon-m-squares-2x2 class="w-5 h-5 text-gray-400" />
            Navigasi Soal
        </h3>

        <div class="flex flex-wrap gap-3">
            <!-- Loop Pilihan Ganda -->
            @for ($i = 1; $i <= $totalPG; $i++)
                @php
                    $key = "q$i";
                    $status = $this->getQuestionStatus($key);
                    $isActive = $activeTab === 'pg' && $currentStep === $i;
                @endphp
                <button wire:click="goToStep('pg', {{ $i }})" @class([
                    'w-10 h-10 rounded-lg text-xs font-bold transition-all border-2',
                    'border-primary-600' => $isActive,
                    'bg-orange-500 text-white border-orange-500' => $status === 'doubtful',
                    'bg-primary-500 text-white border-primary-500' =>
                        $status === 'answered' && !$isActive,
                    'bg-white text-gray-400 border-gray-200 hover:border-primary-300' =>
                        $status === 'unanswered' && !$isActive,
                ])>
                    {{ $i }}
                </button>
            @endfor

            <!-- Divider -->
            <div class="w-px h-10 bg-gray-100 mx-1"></div>

            <!-- Loop Essay -->
            @for ($i = 1; $i <= $totalEssay; $i++)
                @php
                    $stepIdx = $i + $totalPG;
                    $key = "q$stepIdx";
                    $status = $this->getQuestionStatus($key);
                    $isActive = $activeTab === 'essay' && $currentStep === $i;
                @endphp
                <button wire:click="goToStep('essay', {{ $i }})" @class([
                    'w-10 h-10 rounded-lg text-xs font-bold transition-all border-2',
                    'border-primary-600' => $isActive,
                    'bg-orange-500 text-white border-orange-500' => $status === 'doubtful',
                    'bg-primary-500 text-white border-primary-500' =>
                        $status === 'answered' && !$isActive,
                    'bg-white text-gray-400 border-gray-200 hover:border-primary-300' =>
                        $status === 'unanswered' && !$isActive,
                ])>
                    E{{ $i }}
                </button>
            @endfor
        </div>

        <!-- Legend (Keterangan Warna) -->
        <div class="mt-6 flex flex-wrap gap-4 text-[10px] font-bold uppercase tracking-widest text-gray-400">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-white border border-gray-200 rounded"></div> Belum
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-primary-500 rounded"></div> Sudah
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-orange-500 rounded"></div> Ragu-Ragu
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('timerHandler', (initialSeconds) => ({
            remaining: initialSeconds,
            interval: null,
            storageKey: 'exam_end_time_' + @js($exam_id),

            initTimer() {
                let endTime = localStorage.getItem(this.storageKey);

                if (!endTime) {
                    // Jika belum ada di storage, buat waktu akhir baru
                    endTime = new Date().getTime() + (this.remaining * 1000);
                    localStorage.setItem(this.storageKey, endTime);
                }

                this.interval = setInterval(() => {
                    const now = new Date().getTime();
                    const dist = endTime - now;
                    this.remaining = Math.max(0, Math.floor(dist / 1000));

                    if (this.remaining <= 0) {
                        clearInterval(this.interval);
                        localStorage.removeItem(this.storageKey);
                        @this.timeOut(); // Panggil method di Livewire
                    }
                }, 1000);
            },

            formatTime(seconds) {
                const h = Math.floor(seconds / 3600);
                const m = Math.floor((seconds % 3600) / 60);
                const s = seconds % 60;
                return [h, m, s].map(v => v.toString().padStart(2, '0')).join(':');
            }
        }))
    });
</script>
