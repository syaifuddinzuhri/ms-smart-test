<style>
    /* Hanya muncul di perangkat mobile (layar kecil) saat mode landscape */
    @media screen and (orientation: landscape) and (max-device-width: 932px) {
        #portrait-lock-message {
            display: flex !important;
        }

        body {
            overflow: hidden;
        }
    }

    #portrait-lock-message {
        display: none;
        position: fixed;
        inset: 0;
        background: #ffffff;
        z-index: 99999;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 20px;
    }
</style>


<!-- Elemen Pesan -->
<div id="portrait-lock-message">
    <div class="mb-4">
        <svg class="w-16 h-16 text-emerald-600 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
        </svg>
    </div>
    <h2 class="text-xl font-bold text-gray-900">Gunakan Mode Portrait</h2>
    <p class="text-gray-500 mt-2">Ujian ini hanya dapat dikerjakan dalam orientasi layar tegak (Portrait).</p>
</div>
