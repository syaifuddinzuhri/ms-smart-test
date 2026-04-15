<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center min-h-[70vh]">
        <div class="w-full max-w-xl">

            <div class="bg-white border border-gray-200 rounded-xl shadow-xl shadow-gray-200/50 overflow-hidden">

                <div class="bg-gradient-to-r from-primary-50 to-white px-8 py-6 border-b border-gray-100">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-primary-100 rounded-lg text-primary-600">
                            <x-heroicon-s-key class="w-6 h-6" />
                        </div>
                        <h1 class="text-xl md:text-2xl font-black text-gray-900 uppercase tracking-tight">
                            Verifikasi Akses
                        </h1>
                    </div>
                    <p class="text-sm text-gray-500 font-medium">Silahkan masukkan token yang diberikan oleh pengawas untuk membuka soal.</p>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4 mb-4 bg-gray-50 p-4 rounded-2xl border border-gray-100">
                        <div class="space-y-1">
                            <p class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Mata Pelajaran</p>
                            <p class="text-sm font-bold text-gray-800 italic">Matematika</p>
                        </div>
                        <div class="space-y-1 border-l border-gray-200 pl-4">
                            <p class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Kategori</p>
                            <p class="text-sm font-bold text-gray-800 italic">Ujian Akhir Semester</p>
                        </div>
                    </div>

                    <form wire:submit.prevent="validateToken" class="space-y-8">
                        <div class="token-field-container">
                            {{ $this->form }}
                        </div>

                        <div class="flex flex-col gap-4 mt-4">
                            @foreach ($this->getFormActions() as $action)
                                <div class="w-full child-button-full">
                                    {{ $action->size('xl')->extraAttributes(['class' => 'w-full !rounded-xl shadow-lg shadow-primary-200']) }}
                                </div>
                            @endforeach

                            <a href="/student"
                                class="text-xs font-bold text-gray-400 hover:text-danger-500 transition-all tracking-widest uppercase flex items-center justify-center gap-2 py-2">
                                <x-heroicon-m-x-circle class="w-4 h-4" />
                                Batalkan Sesi
                            </a>
                        </div>
                    </form>
                </div>

                <div class="bg-gray-50/50 px-8 py-4 border-t border-gray-100 flex justify-center">
                    <div class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em]">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse"></span>
                        Secure Exam Connection Active
                    </div>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-gray-400 px-6">
                Pastikan Anda tidak membagikan token ini kepada siapapun. Sistem mencatat alamat IP dan ID perangkat Anda saat token divalidasi.
            </p>

        </div>
    </div>

    <style>
        /* Memastikan button dari Filament Actions mengambil lebar penuh jika diinginkan */
        .child-button-full button,
        .child-button-full a {
            width: 100% !important;
            justify-content: center !important;
        }
    </style>
</x-filament-panels::page>
