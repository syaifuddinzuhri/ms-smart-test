<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>Manusgi Smart Test</title>
    <meta name="title" content="Smart Test MA NU Sunan Giri Prigen">
    <meta name="description"
        content="Smart Test MA NU Sunan Giri Prigen adalah sistem ujian online berbasis web yang aman, cepat, dan terintegrasi untuk mendukung pelaksanaan CBT (Computer Based Test) secara efektif dan efisien.">
    <meta name="keywords"
        content="smart test, ma nu sunan giri, ma nu sunan giri prigen, ujian online, CBT, computer based test, sistem ujian sekolah, aplikasi ujian online, ujian berbasis komputer, e-learning, ujian digital, CBT madrasah, ujian MA NU Sunan Giri Prigen">

    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">

    <meta name="google" content="notranslate">

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <meta name="robots" content="noindex, nofollow">

    <link rel="icon" href="/favicon.ico?v=2">
    <link rel="shortcut icon" href="/favicon.ico?v=2">
    <link rel="apple-touch-icon" href="/favicon.ico?v=2">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Manusgi Smart Test">

    <meta name="theme-color" content="#ffffff">

    <!-- Open Graph -->
    <meta property="og:title" content="Smart Test MA NU Sunan Giri Prigen">
    <meta property="og:description"
        content="Platform ujian online modern untuk CBT di MA NU Sunan Giri Prigen yang aman, cepat, dan terintegrasi.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:image" content="{{ env('APP_URL') }}/images/logo.png">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Smart Test MA NU Sunan Giri Prigen">
    <meta name="twitter:description"
        content="Sistem ujian online CBT MA NU Sunan Giri Prigen yang modern, aman, dan efisien.">
    <meta name="twitter:image" content="{{ env('APP_URL') }}/images/logo.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
        }
    </style>
</head>

<body class="antialiased bg-gray-50 text-gray-900">

    <!-- Navbar -->
    <nav class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-md border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-10 w-auto">
                    <span class="font-bold text-xl tracking-tight uppercase">
                        Manusgi <span class="text-emerald-600">Smart Test</span>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden pt-16">
        <!-- Background Decor -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full z-0 pointer-events-none">
            <div class="absolute top-20 left-10 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl text-emerald-500">
            </div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl text-blue-500"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">

                <!-- Info Text -->
                <div class="text-center lg:text-left">
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold uppercase tracking-widest mb-6">
                        <span class="relative flex h-2 w-2">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        Sistem CBT Online Terintegrasi
                    </div>

                    <h1 class="text-5xl lg:text-7xl font-black leading-tight mb-6 text-gray-900">
                        Ujian Lebih <span class="text-emerald-600 italic">Mudah</span> & <span
                            class="text-blue-600 italic">Terukur</span>.
                    </h1>

                    <p class="text-lg text-gray-600 mb-10 max-w-xl mx-auto lg:mx-0 leading-relaxed font-medium">
                        Manusgi Smart Test adalah platform Computer Based Test (CBT) yang dirancang untuk mendukung
                        integritas dan efisiensi evaluasi akademik. Aman, cepat, dan terpantau secara real-time.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        @guest
                            <!-- Tampilan jika BELUM login -->
                            <a href="/student"
                                class="group relative w-full sm:w-auto flex items-center justify-center gap-3 px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-bold transition-all shadow-xl shadow-blue-500/25 active:scale-95">
                                <svg class="w-5 h-5 group-hover:rotate-12 transition" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                    </path>
                                </svg>
                                Masuk Sebagai Siswa
                            </a>

                            <a href="/admin"
                                class="w-full sm:w-auto flex items-center justify-center gap-3 px-8 py-4 bg-white text-gray-900 border border-gray-200 rounded-2xl font-bold hover:bg-gray-50 transition-all shadow-lg active:scale-95">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                    </path>
                                </svg>
                                Panel Admin & Guru
                            </a>
                        @else
                            <!-- Tampilan jika SUDAH login -->
                            @php
                                $isStudent = auth()->user()->role->value === \App\Enums\UserRole::STUDENT->value;
                                $targetUrl = $isStudent ? '/student' : '/admin';
                                $btnColor = $isStudent
                                    ? 'bg-blue-600 hover:bg-blue-700'
                                    : 'bg-emerald-600 hover:bg-emerald-700';
                            @endphp

                            <a href="{{ $targetUrl }}"
                                class="w-full sm:w-auto flex items-center justify-center gap-3 px-10 py-4 {{ $btnColor }} text-white rounded-2xl font-bold transition-all shadow-xl active:scale-95">
                                <svg class="w-5 h-5 animate-bounce-horizontal" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                                Kembali ke Dashboard Anda
                            </a>
                        @endguest
                    </div>
                </div>

                <!-- Hero Image / Illustration -->
                <div class="hidden lg:flex justify-end relative">
                    <div
                        class="relative w-[500px] h-[500px] bg-emerald-600 rounded-[3rem] rotate-3 overflow-hidden shadow-2xl border-4 border-white">
                        <!-- Placeholder Image -->
                        <img src="https://images.unsplash.com/photo-1619462572319-9440a75d7a6b"
                            class="w-full h-full object-cover -rotate-3 scale-110 opacity-90" alt="Student Testing">

                        <!-- Overlay Card -->
                        <div
                            class="absolute bottom-6 left-6 right-6 p-6 bg-white/30 backdrop-blur-md rounded-2xl border border-white/40 text-white">
                            <p class="font-bold text-lg">Real-time Monitoring</p>
                            <p class="text-sm opacity-90 leading-relaxed">Sistem keamanan tinggi untuk mencegah
                                kecurangan saat ujian berlangsung secara otomatis.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-10 border-t border-gray-200 text-center bg-white">
        <p class="text-sm text-gray-500">
            &copy; {{ date('Y') }} Manusgi Smart Test. Seluruh Hak Cipta Dilindungi.
        </p>
    </footer>

</body>

</html>
