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
<meta property="og:image" content="{{ env('APP_URL') }}/images/logo.webp">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Smart Test MA NU Sunan Giri Prigen">
<meta name="twitter:description"
    content="Sistem ujian online CBT MA NU Sunan Giri Prigen yang modern, aman, dan efisien.">
<meta name="twitter:image" content="{{ env('APP_URL') }}/images/logo.webp">

<link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">

<style>
    .attachment__caption,
    .attachment__metadata,
    .attachment__name,
    .attachment__size {
        display: none !important;
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
</style>
