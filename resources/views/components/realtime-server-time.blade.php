<div class="flex items-center gap-3 bg-gray-50 px-3 py-1.5 rounded-full border border-gray-200 w-fit">
    <!-- Status Dot & Clock -->
    <div class="flex items-center gap-2 border-r border-gray-200 pr-3">
        <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
        </span>

        <span id="clock" class="text-[10px] font-mono font-bold text-gray-500 uppercase tracking-wider">
            --:--:-- WIB
        </span>
    </div>

    <!-- Role Badge -->
    @php
        $role = auth()->user()->role;
        $badgeClass = match ($role->value) {
            'teacher' => 'bg-red-100 text-red-700 border-red-200',
            'student' => 'bg-orange-100 text-orange-700 border-orange-200',
            'admin' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            'supervisor' => 'bg-amber-100 text-amber-700 border-amber-200',
            default => 'bg-gray-100 text-gray-700 border-gray-200',
        };
    @endphp

    <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest border {{ $badgeClass }}">
        {{ $role->label() }}
    </span>
</div>

@push('scripts')
    <script>
        function updateClock() {
            const now = new Date();

            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');

            const el = document.getElementById("clock");
            if (el) {
                el.innerText = `${h}:${m}:${s} WIB`;
            }
        }

        updateClock();
        setInterval(updateClock, 1000);
    </script>
@endpush
