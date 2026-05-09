<div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
    <form wire:submit="post">
        <div class="flex gap-3 px-4 pt-4 pb-3">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-amber-500 text-sm font-semibold text-white"
            >
                {{
                    str(auth()->user()->name)
                        ->substr(0, 2)
                        ->upper()
                }}
            </div>
            <div class="min-w-0 flex-1">{{ $this->form }}</div>
        </div>
        <div class="flex items-center justify-end border-t border-gray-100 px-4 py-2.5 dark:border-white/5">
            <button
                type="submit"
                class="bg-primary-600 hover:bg-primary-500 rounded-lg px-4 py-1.5 text-xs font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
            >
                Postar
            </button>
        </div>
    </form>

    <x-filament-actions::modals />
</div>
