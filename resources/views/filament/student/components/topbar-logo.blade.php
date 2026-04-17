<style>
    /* Jika sidebar sedang terbuka (di layar desktop), sembunyikan logo topbar ini */
    /* Karena sudah ada logo di dalam sidebar */
    .fi-sidebar-open .topbar-custom-logo {
        display: none;
    }

    /* Saat sidebar tertutup (collapsed), tampilkan logo ini */
    .fi-sidebar-close .topbar-custom-logo {
        display: flex;
    }
</style>

<div class="topbar-custom-logo flex items-center gap-3 px-3">
    <img src="{{ asset('images/logo.webp') }}" class="h-8 w-auto">
</div>
