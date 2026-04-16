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
            <lottie-player src="{{ asset('lottie/500-search.json') }}" background="transparent" speed="1"
                class="w-full h-auto" loop autoplay>
            </lottie-player>
        </div>

        {{-- TEKS INFORMASI --}}
        <div class="max-w-2xl">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Ups, Ada Gangguan Teknis!</h2>
            <p class="text-gray-500 mb-12 leading-relaxed text-base">
                Maaf atas ketidaknyamanannya. Sepertinya server kami sedang mengalami masalah internal. Tim teknis kami
                sudah diberitahu dan sedang berusaha memperbaikinya segera.
            </p>

            {{-- TOMBOL AKSI --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/') }}"
                    class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-base font-semibold rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-200 active:scale-95">
                    Kembali ke Beranda
                </a>
                <button onclick="window.history.back()"
                    class="inline-flex items-center justify-center px-8 py-3.5 border border-gray-200 text-base font-semibold rounded-xl text-gray-600 bg-white hover:bg-gray-50 transition-all active:scale-95">
                    Halaman Sebelumnya
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 text-left mt-10">
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
                        <a href="mailto:zuhrideveloper@gmail.com" target="_blank"
                            class="text-blue-500">zuhrideveloper@gmail.com</a>
                    </p>
                </div>
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
