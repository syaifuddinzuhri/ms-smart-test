<style>
    .back-to-home {
        color: #9ca3af;
        /* Gray-400 */
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
    }

    /* Hover logic manual berdasarkan Panel */
    .back-to-home:hover {
        @if (filament()->getId() === 'admin')
            color: #2563eb !important;
            /* Blue-600 */
        @else
            color: #10b981 !important;
            /* Emerald-600 */
        @endif
    }

    .back-to-home:hover svg {
        transform: translateX(-4px);
    }

    .back-to-home svg {
        transition: transform 0.2s;
        width: 1rem;
        height: 1rem;
    }
</style>
{{--
<div class="text-center border-t border-gray-100 pt-6">
    <a href="/" class="back-to-home">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        <span>Kembali ke Halaman Utama</span>
    </a>
</div> --}}
