<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>MS Smart Test - Solusi Ujian Online & CBT Modern</title>
    <meta name="title" content="MS Smart Test - Solusi Ujian Online & CBT Modern">
    <meta name="description"
        content="MS Smart Test adalah sistem ujian online berbasis web yang aman, handal, dan terintegrasi. Dirancang untuk mendukung pelaksanaan Computer Based Test (CBT) yang efektif, efisien, dan akurat bagi berbagai lembaga pendidikan.">
    <meta name="keywords"
        content="MS Smart Test, smart test, ujian online, CBT, computer based test, sistem ujian sekolah, aplikasi ujian online, ujian berbasis komputer, e-learning, ujian digital, CBT nasional, platform ujian online, manajemen ujian">

    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">

    <meta name="google" content="notranslate">

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    {{-- <meta name="robots" content="noindex, nofollow"> --}}

    <link rel="icon" href="/favicon.ico?v=2">
    <link rel="shortcut icon" href="/favicon.ico?v=2">
    <link rel="apple-touch-icon" href="/favicon.ico?v=2">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MS Smart Test">

    <meta name="theme-color" content="#ffffff">

    <!-- Open Graph -->
    <meta property="og:title" content="MS Smart Test - Solusi Ujian Online & CBT Modern">
    <meta property="og:description"
        content="MS Smart Test adalah sistem ujian online berbasis web yang aman, handal, dan terintegrasi. Dirancang untuk mendukung pelaksanaan Computer Based Test (CBT) yang efektif, efisien, dan akurat bagi berbagai lembaga pendidikan.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:image" content="{{ env('APP_URL') }}/images/logo.webp">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="MS Smart Test - Solusi Ujian Online & CBT Modern">
    <meta name="twitter:description"
        content="MS Smart Test adalah sistem ujian online berbasis web yang aman, handal, dan terintegrasi. Dirancang untuk mendukung pelaksanaan Computer Based Test (CBT) yang efektif, efisien, dan akurat bagi berbagai lembaga pendidikan.">
    <meta name="twitter:image" content="{{ env('APP_URL') }}/images/logo.webp">

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
                    <img src="{{ asset('images/full-logo.webp') }}" alt="Logo" class="h-10 w-auto">
                    {{-- <span class="font-bold text-xl tracking-tight uppercase">
                        MS <span class="text-emerald-600">Smart Test</span>
                    </span> --}}
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
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-green-500/10 rounded-full blur-3xl text-green-500">
            </div>
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
                            class="text-green-600 italic">Terukur</span>.
                    </h1>

                    <p class="text-lg text-gray-600 mb-10 max-w-xl mx-auto lg:mx-0 leading-relaxed font-medium">
                        MS Smart Test adalah platform Computer Based Test (CBT) yang dirancang untuk mendukung
                        integritas dan efisiensi evaluasi akademik. Aman, cepat, dan terpantau secara real-time.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        @guest
                            <!-- Tampilan jika BELUM login -->
                            <a href="/student"
                                class="group relative w-full sm:w-auto flex items-center justify-center gap-3 px-8 py-4 bg-green-600 hover:bg-green-700 text-white rounded-2xl font-bold transition-all shadow-xl shadow-green-500/25 active:scale-95">
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
                                    ? 'bg-green-600 hover:bg-green-700'
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

    <section class="py-20 bg-emerald-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="space-y-2">
                    <div class="text-4xl lg:text-5xl font-black text-white">50K+</div>
                    <div class="text-emerald-100 text-xs font-bold uppercase tracking-widest">Ujian Selesai</div>
                </div>
                <div class="space-y-2">
                    <div class="text-4xl lg:text-5xl font-black text-white">100+</div>
                    <div class="text-emerald-100 text-xs font-bold uppercase tracking-widest">Institusi</div>
                </div>
                <div class="space-y-2">
                    <div class="text-4xl lg:text-5xl font-black text-white">99.9%</div>
                    <div class="text-emerald-100 text-xs font-bold uppercase tracking-widest">Uptime Server</div>
                </div>
                <div class="space-y-2">
                    <div class="text-4xl lg:text-5xl font-black text-white">24/7</div>
                    <div class="text-emerald-100 text-xs font-bold uppercase tracking-widest">Technical Support</div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl lg:text-4xl font-black text-gray-900 mb-4">Fitur Unggulan <span
                        class="text-emerald-600">MS Smart Test</span></h2>
                <p class="text-gray-500 max-w-2xl mx-auto font-medium">Platform evaluasi digital dengan teknologi
                    terkini untuk memastikan integritas dan kemudahan akses bagi guru maupun siswa.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div
                    class="p-8 rounded-3xl bg-gray-50 border border-gray-100 hover:shadow-xl hover:shadow-emerald-500/5 transition-all group">
                    <div
                        class="w-12 h-12 bg-emerald-100 rounded-2xl flex items-center justify-center text-emerald-600 mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Anti-Cheat System</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Keamanan tingkat tinggi dengan fitur deteksi
                        kecurangan, deteksi aktivitas tab, dan penguncian sistem selama ujian.</p>
                </div>

                <div
                    class="p-8 rounded-3xl bg-gray-50 border border-gray-100 hover:shadow-xl hover:shadow-green-500/5 transition-all group">
                    <div
                        class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center text-green-600 mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Analisis Real-Time</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Pantau progres pengerjaan siswa secara langsung
                        dan dapatkan hasil analisis nilai otomatis seketika setelah ujian selesai.</p>
                </div>

                <div
                    class="p-8 rounded-3xl bg-gray-50 border border-gray-100 hover:shadow-xl hover:shadow-emerald-500/5 transition-all group">
                    <div
                        class="w-12 h-12 bg-emerald-100 rounded-2xl flex items-center justify-center text-emerald-600 mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Performa Tinggi</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Infrastruktur berbasis Cloud yang stabil, mampu
                        menangani ribuan peserta ujian secara bersamaan tanpa kendala.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white relative overflow-hidden">
        <div
            class="absolute top-1/2 left-0 w-full h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent hidden md:block">
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-20">
                <div
                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-green-50 text-green-600 text-[10px] font-black uppercase tracking-[0.2em] mb-4">
                    Workflow
                </div>
                <h2 class="text-4xl lg:text-5xl font-black text-gray-900 mb-6">
                    Mulai Ujian dalam <span
                        class="text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-emerald-900">3
                        Menit</span>
                </h2>
                <p class="text-gray-500 max-w-xl mx-auto font-medium text-lg">
                    Alur kerja yang simpel dan intuitif, dirancang untuk efisiensi waktu pengajar tanpa konfigurasi yang
                    rumit.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                <div class="group relative">
                    <div
                        class="absolute -inset-4 rounded-[2.5rem] bg-gradient-to-b from-emerald-50 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-500">
                    </div>
                    <div class="relative space-y-6 p-2 text-center md:text-left">
                        <div class="inline-flex items-center justify-center">
                            <div
                                class="w-20 h-20 bg-white shadow-xl shadow-emerald-500/10 rounded-3xl flex items-center justify-center text-3xl font-black text-emerald-600 border border-emerald-50 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-500">
                                01
                            </div>
                        </div>
                        <div class="space-y-3">
                            <h3
                                class="text-2xl font-black text-gray-900 group-hover:text-emerald-600 transition-colors">
                                Input Soal</h3>
                            <p class="text-gray-500 leading-relaxed font-medium">
                                Upload soal dengan cepat melalui <span class="text-gray-900 font-bold">Excel Smart
                                    Import</span> atau editor <span class="text-emerald-600 font-bold">LaTeX</span>
                                untuk rumus matematika kompleks.
                            </p>
                        </div>
                        <div class="hidden md:block absolute top-10 -right-4 translate-x-1/2 opacity-20">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="group relative">
                    <div
                        class="absolute -inset-4 rounded-[2.5rem] bg-gradient-to-b from-emerald-50 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-500">
                    </div>
                    <div class="relative space-y-6 p-2 text-center md:text-left">
                        <div class="inline-flex items-center justify-center">
                            <div
                                class="w-20 h-20 bg-white shadow-xl shadow-emerald-500/10 rounded-3xl flex items-center justify-center text-3xl font-black text-emerald-600 border border-emerald-50 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-500">
                                02
                            </div>
                        </div>
                        <div class="space-y-3">
                            <h3
                                class="text-2xl font-black text-gray-900 group-hover:text-emerald-600 transition-colors">
                                Atur Sesi</h3>
                            <p class="text-gray-500 leading-relaxed font-medium">
                                Kendali penuh pada jadwal, durasi, dan peserta. Aktifkan <span
                                    class="text-gray-900 font-bold">AI Monitoring</span> dan penguncian browser dengan
                                satu klik.
                            </p>
                        </div>
                        <div class="hidden md:block absolute top-10 -right-4 translate-x-1/2 opacity-20">
                            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="group relative">
                    <div
                        class="absolute -inset-4 rounded-[2.5rem] bg-gradient-to-b from-green-50 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-500">
                    </div>
                    <div class="relative space-y-6 p-2 text-center md:text-left">
                        <div class="inline-flex items-center justify-center">
                            <div
                                class="w-20 h-20 bg-white shadow-xl shadow-green-500/10 rounded-3xl flex items-center justify-center text-3xl font-black text-green-600 border border-green-50 group-hover:scale-110 group-hover:bg-green-600 group-hover:text-white transition-all duration-500">
                                03
                            </div>
                        </div>
                        <div class="space-y-3">
                            <h3 class="text-2xl font-black text-gray-900 group-hover:text-green-600 transition-colors">
                                Pantau & Hasil</h3>
                            <p class="text-gray-500 leading-relaxed font-medium">
                                Pantau aktivitas peserta secara <span
                                    class="text-green-600 font-bold italic">Real-time</span> dan unduh laporan analitik
                                nilai otomatis (PDF/Excel) seketika.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div
                class="bg-gradient-to-br from-emerald-500 to-green-700 rounded-[3rem] p-10 lg:p-16 text-center text-white shadow-2xl relative overflow-hidden group">
                <div
                    class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20 blur-3xl group-hover:bg-white/20 transition-all">
                </div>

                <h2 class="text-3xl lg:text-4xl font-black mb-6 relative z-10">Siap mendigitalisasi ujian Anda?</h2>
                <p class="text-emerald-50 mb-10 text-lg font-medium opacity-90 relative z-10">Hubungi tim kami untuk
                    konsultasi, demo aplikasi, atau penyesuaian kebutuhan sistem di institusi Anda.</p>

                <div class="flex flex-col sm:flex-row justify-center gap-4 relative z-10">
                    <a href="https://wa.me/6285648989767" target="_blank"
                        class="px-8 py-4 bg-white text-emerald-700 rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-emerald-50 transition-all shadow-xl">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                        </svg>
                        Hubungi WhatsApp
                    </a>
                    <a href="mailto:zuhrideveloper@gmail.com"
                        class="px-8 py-4 bg-emerald-700/20 text-white border border-white/20 rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-emerald-700/40 transition-all backdrop-blur-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                        Email Support
                    </a>
                </div>
            </div>
        </div>
    </section>

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

</body>

</html>
