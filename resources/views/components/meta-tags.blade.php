<meta name="title" content="MS Smart Test - Solusi Ujian Online & CBT Modern">
<meta name="description"
    content="MS Smart Test adalah sistem ujian online berbasis web yang aman, handal, dan terintegrasi. Dirancang untuk mendukung pelaksanaan Computer Based Test (CBT) yang efektif, efisien, dan akurat bagi berbagai lembaga pendidikan.">
<meta name="keywords"
    content="MS Smart Test, smart test, ujian online, CBT, computer based test, sistem ujian sekolah, aplikasi ujian online, ujian berbasis komputer, e-learning, ujian digital, CBT nasional, platform ujian online, manajemen ujian">

<meta http-equiv="X-Content-Type-Options" content="nosniff">

<meta name="google" content="notranslate">

<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

<meta name="robots" content="noindex, nofollow">

<link rel="icon" href="/favicon.ico?v=2">
<link rel="shortcut icon" href="/favicon.ico?v=2">
<link rel="apple-touch-icon" href="/favicon.ico?v=2">

<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="MS Smart Test">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

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

<link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">

<style>
    .attachment__caption,
    .attachment__metadata,
    .attachment__name,
    .attachment__size {
        display: none !important;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .soal-content {
        /* Letakkan Amiri di depan agar diprioritaskan oleh browser */
        unicode-bidi: plaintext;
        text-align: start !important;
        line-height: 2;
        /* Sangat penting untuk teks Arab agar harakat tidak bertumpuk */
        visibility: visible;
    }

    /* Spesifik untuk teks yang terdeteksi sebagai Arab */
    /* Jika konten Anda memiliki atribut lang="ar" */
    .soal-content:lang(ar) {
        font-family: 'Amiri', ui-sans-serif, system-ui, serif;
        font-size: 1.25rem;
        /* Teks Arab biasanya terlihat lebih kecil, perlu diperbesar */
        line-height: 2.2;
        direction: rtl;
    }

    /* Fallback jika tidak ada atribut lang, pastikan font-size cukup besar */
    /* Karena Amiri adalah font serif, kita gunakan stack serif sebagai cadangan */
    [dir="rtl"].soal-content,
    .soal-content[style*="direction: rtl"],
    .soal-content[style*="text-align: right"] {
        font-family: 'Amiri', ui-sans-serif, system-ui, serif;
        font-size: 1.25rem;
    }

    mark {
        background-color: yellow;
        color: black;
    }

    body {
        overscroll-behavior-y: !contain;
    }

    .fi-user-menu { display: none !important; }
</style>
