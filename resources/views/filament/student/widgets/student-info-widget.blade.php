<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-4 border-b border-gray-100 pb-4 mb-4">
            <div class="flex-shrink-0">
                <div
                    class="w-12 h-12 rounded-full bg-primary-50 flex items-center justify-center text-primary-600 border border-primary-100">
                    <x-heroicon-s-user class="w-8 h-8" />
                </div>
            </div>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-md md:text-xl font-black text-gray-900 leading-tight">{{ auth()->user()->name }}</h2>
                    <p class="text-xs md:text-sm text-gray-500 font-medium">NISN: 00123456789</p>
                </div>
                <span
                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-bold bg-green-50 text-green-600 border border-green-100">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                    Aktif
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="space-y-1">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Kelas & Jurusan</p>
                <p class="text-sm font-semibold text-gray-800">XII Teknik Informatika - 1</p>
            </div>

            <div class="space-y-1">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Tempat, Tanggal Lahir</p>
                <p class="text-sm font-semibold text-gray-800">Jakarta, 12 Agustus 2008</p>
            </div>

            <div class="space-y-1">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Jenis Kelamin</p>
                <p class="text-sm font-semibold text-gray-800">Laki-laki</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
