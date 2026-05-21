@props(['question'])

<div {{ $attributes->merge(['class' => 'space-y-4 my-4']) }}>
    {{-- ATTACHMENTS --}}
    @if ($question->attachments && $question->attachments->count() > 0)
        <div class="flex flex-wrap gap-3">
            @foreach ($question->attachments as $att)
                @php
                    $ext = strtolower(pathinfo($att->file_path, PATHINFO_EXTENSION));
                    $src = $att->url;

                    // Logic penentuan tipe
                    $type = 'other';
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                        $type = 'image';
                    } elseif (in_array($ext, ['mp4', 'webm', 'ogg'])) {
                        $type = 'video';
                    } elseif (in_array($ext, ['mp3', 'wav', 'ogg'])) {
                        $type = 'audio';
                    } elseif ($ext === 'pdf') {
                        $type = 'pdf';
                    }

                    // Ikon Preview
                    $icon = match ($type) {
                        'video' => 'heroicon-o-play-circle',
                        'audio' => 'heroicon-o-musical-note',
                        'pdf' => 'heroicon-o-document-text',
                        default => 'heroicon-o-document',
                    };
                @endphp

                <div class="group relative flex-shrink-0 cursor-pointer" x-data="{}"
                    @click="$dispatch('open-media-lightbox', { src: '{{ $src }}', type: '{{ $type }}' })">

                    <div @class([
                        'h-24 w-32 overflow-hidden rounded-xl border-2 transition-all duration-300 shadow-sm flex flex-col items-center justify-center',
                        'border-gray-100 bg-gray-50 group-hover:border-indigo-500' =>
                            $type !== 'image',
                        'border-gray-100 group-hover:border-green-500' => $type === 'image',
                    ])>
                        @if ($type === 'image')
                            <img src="{{ $src }}"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110">
                        @else
                            <x-dynamic-component :component="$icon"
                                class="h-8 w-8 text-gray-400 group-hover:text-indigo-500" />
                            <span
                                class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-500 group-hover:text-indigo-600">{{ $ext }}</span>
                        @endif
                    </div>

                    {{-- Hover Overlay --}}
                    <div
                        class="absolute inset-0 flex items-center justify-center rounded-xl bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                        <span
                            class="rounded bg-white/20 px-2 py-1 text-[9px] font-black text-white uppercase tracking-tighter">Lihat
                            Detail</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- EXTERNAL LINK (Jika ingin di popup juga) --}}
    @if ($question->external_link)
        <div class="flex">
            <button x-data="{}"
                @click="$dispatch('open-media-lightbox', { src: '{{ $question->external_link }}', type: 'pdf' })"
                class="inline-flex items-center gap-2 rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-1.5 text-[11px] font-bold text-indigo-700 shadow-sm transition-all hover:bg-indigo-100">
                <x-heroicon-m-globe-alt class="h-3.5 w-3.5" />
                <span>Buka Link</span>
            </button>
        </div>
    @endif
</div>
