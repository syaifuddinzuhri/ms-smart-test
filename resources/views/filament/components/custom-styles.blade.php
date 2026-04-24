{{-- resources/views/filament/admin/custom-styles.blade.php --}}
<style>
    /* 1. Sidebar Background & Border */
    aside.fi-sidebar {
        background-color: #f8fafc !important;
        border-right: 1px solid #e5e7eb;
    }

    .fi-sidebar-header {
        background-color: transparent !important;
    }

    /* 2. Content Width (Full Width) */
    .fi-main-ctn>main {
        max-width: 100% !important;
        /* Gunakan 100% atau 1600px sesuai keinginan */
        padding-left: 2rem !important;
        padding-right: 2rem !important;
    }

    .fi-main-ctn {
        display: flex !important;
        flex-direction: column !important;
        min-height: 100vh !important;
    }

    .fi-main {
        flex: 1 !important;
        /* Ini akan mengambil sisa ruang kosong yang tersedia */
        display: flex !important;
        flex-direction: column !important;
    }

    /* 3. Pastikan div di dalam .fi-main (konten asli) tetap meluas */
    .fi-main>div {
        flex: 1 !important;
    }


    /* 3. Responsive Padding */
    @media (max-width: 1024px) {
        .fi-main-ctn>main {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
    }

    @font-face {
        font-family: 'JetBrains Mono Local';
        src: url('/fonts/jetbrains/JetBrainsMono-ExtraBold.ttf') format('truetype');
        font-weight: 800;
        font-style: normal;
        font-display: swap;
    }

    .font-token {
        font-family: 'JetBrains Mono Local', monospace !important;
        letter-spacing: 0.1em;
        font-variant-ligatures: none;
    }
</style>

{{-- Tailwind CDN & Config --}}
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        darkMode: "class",
        corePlugins: {
            preflight: false,
        }
    }
</script>
