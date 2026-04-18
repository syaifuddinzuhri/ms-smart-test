<div class="space-y-6">
    <div
        class="flex flex-col md:flex-row gap-4 justify-between items-stretch bg-white p-2 rounded-xl shadow-sm border border-gray-200">

        <div class="flex bg-gray-100 p-1 rounded-xl flex-1 items-stretch">
            <button wire:click="setTab('pg')" @class([
                'flex-1 py-2 px-4 text-sm font-bold rounded-lg transition-all flex items-center justify-center',
                'bg-white shadow text-primary-600' => $activeTab === 'pg',
                'text-gray-500 hover:text-gray-700' => $activeTab !== 'pg',
            ])>
                Pilihan Ganda ({{ $totalPG }})
            </button>
            <button wire:click="setTab('essay')" @class([
                'flex-1 py-2 px-4 text-sm font-bold rounded-lg transition-all flex items-center justify-center',
                'bg-white shadow text-primary-600' => $activeTab === 'essay',
                'text-gray-500 hover:text-gray-700' => $activeTab !== 'essay',
            ])>
                Essai / Uraian ({{ $totalEssay }})
            </button>
        </div>

        <div x-data="timerHandler(@js($durationInSeconds))" x-init="initTimer()" class="flex">
            <div
                class="px-6 py-2 bg-red-50 flex flex-col justify-center items-center border border-red-100 rounded-xl w-full md:w-auto">
                <span class="text-[10px] uppercase tracking-wider font-bold text-red-400 leading-none mb-1">Sisa
                    Waktu</span>
                <span class="text-red-600 font-mono font-black text-xl leading-none" x-text="formatTime(remaining)">
                    00:00:00
                </span>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-2 min-h-[200px] relative shadow-sm overflow-hidden">
        <div class="absolute top-0 left-0 flex overflow-hidden rounded-br-xl">
            <div class="bg-green-600 group-hover:bg-green-700 transition-colors text-white px-3 py-1 shadow-sm">
                <div class="flex items-center gap-1.5 leading-none">
                    <span class="text-[9px] uppercase tracking-wider font-semibold opacity-80">Soal</span>
                    <span class="text-sm font-black">{{ $currentStep }}</span>
                </div>
            </div>
            <div class="flex items-center bg-gray-50 border-gray-200 px-2 gap-2">
                <span class="text-xs font-black uppercase tracking-[0.2em] text-gray-400">
                    {{ $activeTab === 'pg' ? 'Pilihan Ganda' : 'Essay' }}
                </span>
            </div>
        </div>
        <div class="absolute top-0 right-0 flex overflow-hidden rounded-br-xl">
            <div class="flex md:flex-row flex-col md:justify-between md:items-center p-4">
                <span class="text-xs font-bold text-primary-600 bg-primary-50 py-1 rounded-full">
                    Selesai: {{ count(array_filter($data)) }} / {{ $totalPG + $totalEssay }}
                </span>
            </div>
        </div>

        <div class="px-4 py-2 mt-10 border-t border-gray-100 " id="soal-container">
            {{ $this->form }}
        </div>
    </div>

    <div
        class="flex flex-col md:flex-row items-center justify-between gap-4 bg-gray-50 p-4 rounded-xl border border-gray-200 mt-8">

        <div
            class="grid gap-2 w-full md:w-auto
        {{ $this->isAbsoluteFirst ? 'grid-cols-2 justify-center' : 'grid-cols-3' }}">
            @if (!$this->isAbsoluteFirst)
                <x-filament::button color="gray" outlined wire:click="previous" icon="heroicon-m-arrow-left"
                    :disabled="$activeTab === 'pg' && $currentStep === 1" class="w-full">
                    <span class="hidden md:inline">Sebelumnya</span>
                </x-filament::button>
            @endif
            <x-filament::button tag="button" wire:click="toggleDoubt" color="warning" :outlined="!in_array($this->currentQuestionId, $doubtfulQuestions)"
                class="w-full">
                <span class="md:hidden">Ragu</span>
                <span class="hidden md:inline">Ragu-Ragu</span>
            </x-filament::button>
            @if (!$this->isAbsoluteLast)
                <x-filament::button color="primary" wire:click="next" icon-position="after"
                    icon="heroicon-m-arrow-right" class="w-full">
                    <span class="hidden md:inline">Selanjutnya</span>
                </x-filament::button>
            @else
                <x-filament::button color="primary" wire:click="saveAnswer('{{ $this->currentQuestionId }}', true)"
                    icon="heroicon-m-check-circle" class="w-full">
                    <span class="inline">Simpan</span>
                </x-filament::button>
            @endif
        </div>

    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 flex items-center gap-2">
            <x-heroicon-m-squares-2x2 class="w-5 h-5 text-gray-400" />
            Navigasi Soal
        </h3>
        <div class="flex flex-wrap gap-3" wire:key="action-bar-soal-{{ $this->currentQuestionId }}">
            <!-- Loop Pilihan Ganda (PG) -->
            @foreach ($this->pgQuestions as $index => $q)
                @php
                    $stepNumber = $index + 1;
                    $status = $this->getQuestionStatus($q->id);
                    $isActive = $activeTab === 'pg' && $currentStep === $stepNumber;
                @endphp
                <button wire:key="nav-pg-{{ $q->id }}" wire:click="goToStep('pg', {{ $stepNumber }})"
                    @class([
                        'w-10 h-10 rounded-lg text-xs font-bold transition-all border-2 flex items-center justify-center',
                        'ring-1 ring-gray-400 z-10 scale-110 shadow-md font-extrabold' => $isActive,
                        'ring-green-500' =>
                            $isActive && ($status === 'answered' && $status !== 'doubtful'),
                        'ring-orange-600' => $isActive && $status === 'doubtful',
                        'bg-orange-500 text-white border-orange-400' => $status === 'doubtful',
                        'bg-primary-500 text-white border-green-400' =>
                            $status === 'answered' && $status !== 'doubtful',
                        'bg-white text-gray-400 border-gray-200' => $status === 'unanswered',
                        'text-primary-600' => $status === 'unanswered' && $isActive,
                    ])>
                    {{-- Label PG harus murni angka, jangan pakai $activeTab --}}
                    {{ $stepNumber }}
                </button>
            @endforeach

            @if ($totalPG > 0 && $totalEssay > 0)
                <div class="w-px h-10 bg-gray-200 mx-1" wire:key="divider"></div>
            @endif

            <!-- Loop Essay -->
            @foreach ($this->essayQuestions as $index => $q)
                @php
                    $stepNumber = $index + 1;
                    $status = $this->getQuestionStatus($q->id);
                    $isActive = $activeTab === 'essay' && $currentStep === $stepNumber;
                @endphp
                <button wire:key="nav-essay-{{ $q->id }}" wire:click="goToStep('essay', {{ $stepNumber }})"
                    @class([
                        'w-10 h-10 rounded-lg text-xs font-bold transition-all border-2 flex items-center justify-center',
                        'ring-1 ring-gray-400 z-10 scale-110 shadow-md font-extrabold' => $isActive,
                        'ring-green-500' =>
                            $isActive && ($status === 'answered' && $status !== 'doubtful'),
                        'ring-orange-600' => $isActive && $status === 'doubtful',
                        'bg-orange-500 text-white border-orange-400' => $status === 'doubtful',
                        'bg-primary-500 text-white border-green-400' =>
                            $status === 'answered' && $status !== 'doubtful',
                        'bg-white text-gray-400 border-gray-200' => $status === 'unanswered',
                        'text-primary-600' => $status === 'unanswered' && $isActive,
                    ])>
                    E{{ $stepNumber }}
                </button>
            @endforeach
        </div>

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

        <div class="w-full flex justify-center md:justify-end mt-4">
            {{ $this->submitAction }}
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('timerHandler', (initialSeconds) => ({
            remaining: initialSeconds,
            lastSync: initialSeconds,
            isFinishing: false,

            initTimer() {
                this.interval = setInterval(async () => {
                    if (this.isFinishing) return;

                    if (this.remaining > 0) {
                        this.remaining--;

                        // SINKRONISASI SETIAP 30 DETIK
                        if (this.lastSync - this.remaining >= 30) {
                            // Kirim ke server dan dapatkan durasi 'resmi' dari server
                            const serverDuration = await @this.updateRemainingTime(this
                                .remaining);

                            // Update timer lokal dengan data server (menangani penambahan waktu admin)
                            this.remaining = serverDuration;
                            this.lastSync = serverDuration;
                        }
                    } else {
                        this.isFinishing = true;
                        clearInterval(this.interval);
                        @this.timeOut();

                        setTimeout(() => {
                            window.location.href = '/student';
                        }, 5000);
                    }
                }, 1000);

                window.addEventListener('prepare-navigation', () => {
                    this.isFinishing = true;
                    clearInterval(this.interval);
                });
            },

            formatTime(seconds) {
                const h = Math.floor(seconds / 3600);
                const m = Math.floor((seconds % 3600) / 60);
                const s = seconds % 60;
                return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
            }
        }))
    });
</script>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('step-changed', () => {
            const isMobile = window.innerWidth <= 768;

            if (isMobile) {
                const element = document.getElementById('soal-container');
                const topbarOffset = 80;

                const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
                const offsetPosition = elementPosition - topbarOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });

            } else {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth' // Gunakan 'smooth' untuk efek halus, atau 'auto' untuk instan
                });
            }
        });
    });
</script>
