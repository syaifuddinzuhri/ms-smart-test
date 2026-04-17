{{-- <script>
    function applyKatex(target = document.body) {
        // Cari semua elemen soal di dalam target
        const containers = target.querySelectorAll('.soal-content');

        containers.forEach((el) => {
            // Cek apakah sudah pernah dirender untuk menghindari double render
            if (el.classList.contains('katex-rendered')) return;

            try {
                renderMathInElement(el, {
                    delimiters: [
                        { left: '$$', right: '$$', display: true },
                        { left: '$', right: '$', display: false },
                        { left: '\\(', right: '\\)', display: false },
                        { left: '\\[', right: '\\]', display: true }
                    ],
                    throwOnError: false
                });
                // Tandai elemen agar tidak dirender ulang
                el.classList.add('katex-rendered');
            } catch (e) {
                console.error("KaTeX error:", e);
            }
        });
    }

    // Jalankan saat halaman siap
    document.addEventListener('DOMContentLoaded', () => {
        applyKatex();

        // Gunakan MutationObserver untuk memantau kapan soal muncul (setelah filter)
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length) {
                    applyKatex();
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });

    // Integrasi khusus Livewire 3
    document.addEventListener('livewire:initialized', () => {
        Livewire.hook('request.cycle.finished', () => {
            setTimeout(() => { applyKatex(); }, 50);
        });
    });
</script> --}}

{{-- resources/views/components/latex-renderer.blade.php --}}
<script>
    (function() {
        /**
         * Fungsi utama untuk merender KaTeX pada elemen .soal-content
         */
        const renderSoalLatex = () => {
            if (typeof renderMathInElement !== 'function') return;

            // Cari elemen yang BELUM ditandai 'katex-done'
            const elements = document.querySelectorAll('.soal-content:not(.katex-done)');

            elements.forEach((el) => {
                try {
                    renderMathInElement(el, {
                        delimiters: [{
                                left: '$$',
                                right: '$$',
                                display: true
                            },
                            {
                                left: '$',
                                right: '$',
                                display: false
                            },
                            {
                                left: '\\(',
                                right: '\\)',
                                display: false
                            },
                            {
                                left: '\\[',
                                right: '\\]',
                                display: true
                            }
                        ],
                        throwOnError: false
                    });
                    // Tandai agar tidak diproses ulang oleh observer
                    el.classList.add('katex-done');
                } catch (e) {
                    console.warn("KaTeX render error:", e);
                }
            });
        };

        // 1. Jalankan saat inisialisasi pertama (Non-SPA load)
        document.addEventListener('livewire:initialized', () => {
            renderSoalLatex();

            // 2. Hook untuk menangani navigasi SPA & Update Komponen (Hapus, Filter, dll)
            // morph.updated mencakup perpindahan halaman via wire:navigate (SPA)
            Livewire.hook('morph.updated', ({
                el,
                component
            }) => {
                renderSoalLatex();
            });
        });

        // 3. Fallback: Gunakan MutationObserver untuk perubahan DOM di luar Livewire
        // atau jika ada konten yang dimuat sangat lambat
        const observer = new MutationObserver((mutations) => {
            // Debounce kecil agar tidak terlalu sering memicu querySelector
            clearTimeout(window.katexTimeout);
            window.katexTimeout = setTimeout(renderSoalLatex, 50);
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    })();
</script>
