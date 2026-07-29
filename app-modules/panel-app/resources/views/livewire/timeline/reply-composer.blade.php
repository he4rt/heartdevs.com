<div
    x-data="{ showUpload: false }"
    class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5"
>
    <form wire:submit="reply">
        <div class="flex gap-2 px-3 pt-3 pb-2 sm:gap-3 sm:px-4 sm:pt-4 sm:pb-3">
            @if ($this->avatarUrl)
                <img
                    src="{{ $this->avatarUrl }}"
                    alt="{{ auth()->user()->name }}"
                    class="hidden h-8 w-8 shrink-0 rounded-full object-cover sm:block"
                />
            @else
                <div
                    class="hidden h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-amber-500 text-xs font-semibold text-white sm:flex"
                >
                    {{
                        str(auth()->user()->name)
                            ->substr(0, 2)
                            ->upper()
                    }}
                </div>
            @endif
            <div class="[&_.fi-fo-field-wrp]:gap-0 [&_.fi-fo-field-wrp-label]:hidden min-w-0 flex-1">
                {{ $this->form }}
            </div>
        </div>
        <div class="flex items-center justify-between border-t border-gray-100 px-3 py-2.5 sm:px-4 dark:border-white/5">
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    @click="showUpload = !showUpload"
                    class="hover:bg-primary-500/10 hover:text-primary-500 rounded-full p-1.5 text-gray-400 transition dark:text-gray-500"
                    :class="showUpload && 'text-primary-500 bg-primary-500/10'"
                    title="Adicionar imagens"
                >
                    <x-heroicon-o-photo class="h-4 w-4" />
                </button>
            </div>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50"
                class="bg-primary-600 hover:bg-primary-500 rounded-lg px-4 py-1.5 text-xs font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
            >
                Responder
            </button>
        </div>
    </form>

    <x-filament-actions::modals />
</div>
