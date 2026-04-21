<footer class="py-6 border-t border-gray-100 bg-white">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">

            <div class="flex flex-col md:flex-row items-center gap-3">
                <div class="w-10 h-10 flex items-center justify-center shadow-sm">
                    <img src="{{ asset('images/logo.webp') }}" />
                </div>

                <div class="text-center md:text-left">
                    <div class="text-[14px] text-gray-700 font-bold tracking-tight">
                        &copy; {{ date('Y') }}
                        <span class="font-bold tracking-tight uppercase">
                            MS <span class="text-green-600">Smart Test</span>
                        </span>
                    </div>
                    <div class="text-[10px] text-gray-400 uppercase tracking-widest leading-tight">
                        Advanced Examination System
                    </div>
                </div>
            </div>

            <div
                class="flex flex-col items-center md:items-end border-t md:border-t-0 pt-4 md:pt-0 border-gray-100 w-full md:w-auto">
                <div class="text-[10px] text-gray-400 uppercase tracking-widest mb-1">
                    Powered by
                </div>
                <div class="text-[12px] font-medium text-gray-500">
                    Developed by <span class="font-bold text-gray-700">Syaifuddin Zuhri</span>
                </div>
            </div>

        </div>
    </div>
</footer>
