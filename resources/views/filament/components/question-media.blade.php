@props(['question'])

<div {{ $attributes->merge(['class' => 'space-y-4 mb-4']) }}>
    {{-- ATTACHMENTS --}}
    @if ($question->attachments && $question->attachments->count() > 0)
        <div class="flex flex-wrap gap-3">
            @foreach ($question->attachments as $att)
                @php
                    // Deteksi manual extension atau gunakan helper detectFileType yang Anda punya
                    $ext = pathinfo($att->file_path, PATHINFO_EXTENSION);
                    $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                @endphp

                <div class="group relative flex-shrink-0">
                    @if ($isImage)
                        <div
                            class="h-24 w-32 overflow-hidden rounded-xl border-2 border-gray-100 shadow-sm transition-all hover:border-green-500">
                            <img src="{{ asset('storage/' . $att->file_path) }}"
                                class="h-full w-full object-cover transition-transform duration-300 hover:scale-110">
                        </div>
                    @else
                        <div
                            class="flex h-24 w-32 flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 transition-all hover:bg-gray-100">
                            <x-heroicon-o-document-arrow-down class="h-8 w-8 text-gray-400" />
                            <span
                                class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">{{ $ext }}</span>
                        </div>
                    @endif

                    {{-- Overlay Preview --}}
                    <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank"
                        class="absolute inset-0 flex items-center justify-center rounded-xl bg-black/40 opacity-0 transition-opacity hover:opacity-100">
                        <span
                            class="rounded bg-white/20 px-2 py-1 text-[9px] font-black text-white backdrop-blur-sm">BUKA</span>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- EXTERNAL LINK --}}
    @if ($question->external_link)
        <div class="flex">
            <a href="{{ $question->external_link }}" target="_blank"
                class="inline-flex items-center gap-2 rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-1.5 text-[11px] font-bold text-indigo-700 shadow-sm transition-all hover:bg-indigo-100 hover:shadow-indigo-100">
                <x-heroicon-m-globe-alt class="h-3.5 w-3.5" />
                <span>Buka Link</span>
                <x-heroicon-m-arrow-top-right-on-square class="h-3 w-3 opacity-50" />
            </a>
        </div>
    @endif
</div>
