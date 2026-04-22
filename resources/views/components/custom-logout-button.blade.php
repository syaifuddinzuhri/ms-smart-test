<form action="{{ filament()->getLogoutUrl() }}" method="post" class="flex items-center">
    @csrf
    <button type="submit" 
            title="Keluar"
            class="fi-icon-btn relative flex items-center justify-center rounded-full p-2 text-red-400 hover:bg-red-500/10 focus:outline-none dark:text-red-500 dark:hover:bg-red-400/10"
            style="margin-inline-start: 1rem;">
        <x-heroicon-o-arrow-right-on-rectangle class="h-6 w-6 text-red-500" />
    </button>
</form>