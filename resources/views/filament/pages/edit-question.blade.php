<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" size="lg">
                Simpan Soal ke Bank Soal
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
