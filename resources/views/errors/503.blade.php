<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Kesalahan Server</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap"
        rel="stylesheet">

    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 flex items-center justify-center min-h-screen p-6">
    <div class="max-w-3xl w-full text-center flex flex-col items-center">

        {{-- CONTAINER LOTTIE --}}
        <div class="w-full max-w-sm mb-10">
            {{--
                Contoh JSON Animasi 404.
                Anda bisa mengganti URL src ini dengan file JSON Lottie lokal Anda
                (misal: asset('lottie/404-search.json'))
            --}}
            <lottie-player src="{{ asset('lottie/503-search.json') }}" background="transparent" speed="1"
                class="w-full h-auto" loop autoplay>
            </lottie-player>
        </div>

        {{-- TEKS INFORMASI --}}
        {{-- Ganti bagian TEKS INFORMASI dengan ini --}}
        <div class="max-w-2xl">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-2 tracking-tight">Sistem Sedang Istirahat Sejenak</h1>

            <p class="text-gray-500 mb-10 leading-relaxed text-lg">
                Maaf, terjadi kesalahan tak terduga di dalam sistem kami. Jangan khawatir, tim teknis kami telah
                menerima laporan otomatis dan sedang berupaya memulihkannya kembali.
            </p>

            {{-- SEKSI BANTUAN / TIPS --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10 text-left">
                <div class="p-4 bg-white border border-gray-100 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-1.5 bg-emerald-50 rounded-lg text-emerald-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Coba Refresh</h3>
                    </div>
                    <p class="text-gray-500 text-sm leading-snug">Seringkali masalah ini hanya bersifat sementara. Coba
                        segarkan halaman dalam beberapa saat.</p>
                </div>

                <div class="p-4 bg-white border border-gray-100 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-1.5 bg-blue-50 rounded-lg text-blue-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Pusat Bantuan</h3>
                    </div>
                    <p class="text-gray-500 text-sm leading-snug">
                        Jika masalah terus berulang, silakan hubungi
                        <a href="mailto:zuhrideveloper@gmail.com" target="_blank" class="text-blue-500">zuhrideveloper@gmail.com</a>
                    </p>
                </div>
            </div>

            {{-- TOMBOL AKSI --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/') }}"
                    class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-base font-bold rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-200 active:scale-95">
                    Kembali ke Beranda
                </a>
                <button onclick="location.reload()"
                    class="inline-flex items-center justify-center px-8 py-3.5 border border-gray-200 text-base font-semibold rounded-xl text-gray-600 bg-white hover:bg-gray-50 transition-all active:scale-95">
                    Muat Ulang Halaman
                </button>
            </div>
        </div>

        <p class="mt-20 text-gray-400 text-xs tracking-wide uppercase">
            &copy; {{ date('Y') }} {{ config('app.name') }}
        </p>
    </div>
    <script>
        document.oncontextmenu = function() {
            return false;
        };
    </script>
</body>

</html>
