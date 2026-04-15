<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2 text-primary-600">
                <x-heroicon-m-information-circle class="w-6 h-6" />
                <span class="text-sm md:text-lg font-black uppercase tracking-tight">Ketentuan & Tata Tertib Ujian</span>
            </div>
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 py-2">
            <div class="space-y-4">
                <div class="flex gap-3">
                    <div
                        class="flex-shrink-0 w-8 h-8 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center font-bold text-sm">
                        1</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Jadwal & Durasi</p>
                        <p class="text-xs text-gray-500">Ujian hanya dapat diakses sesuai jadwal yang
                            ditentukan. Pastikan Anda menyelesaikan ujian sebelum durasi habis.</p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <div
                        class="flex-shrink-0 w-8 h-8 bg-red-100 text-red-600 rounded-full flex items-center justify-center font-bold text-sm">
                        2</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Integritas & Kejujuran</p>
                        <p class="text-xs text-red-600 font-medium">
                            Dilarang berpindah tab, meminimalkan browser, atau membuka aplikasi lain selama ujian
                            berlangsung.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex gap-3">
                    <div
                        class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-sm">
                        3</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Sistem Device Lock</p>
                        <p class="text-xs text-gray-500">Jika terdeteksi pelanggaran, sesi akan
                            otomatis <span class="font-bold underline">TERKUNCI</span>. Anda wajib melapor ke pengawas
                            untuk mendapatkan token baru.</p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <div
                        class="flex-shrink-0 w-8 h-8 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center font-bold text-sm">
                        4</div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Koneksi & Daya</p>
                        <p class="text-xs text-gray-500">Pastikan koneksi internet stabil dan baterai
                            perangkat mencukupi. Jawaban tersimpan otomatis setiap Anda berpindah soal.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center gap-3">
            <span class="relative flex h-2 w-2 ml-2">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
            </span>
            <p class="text-[10px] uppercase font-black text-gray-400 tracking-[0.1em]">Sistem pemantauan aktif selama
                ujian berlangsung</p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
