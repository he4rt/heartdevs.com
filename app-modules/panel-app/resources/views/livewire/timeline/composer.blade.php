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
            <div class="min-w-0 flex-1">
                <textarea
                    wire:model="content"
                    placeholder="O que está acontecendo?"
                    rows="2"
                    class="w-full resize-none border-0 bg-transparent p-0 text-sm text-gray-900 placeholder-gray-400 focus:ring-0 dark:text-white dark:placeholder-gray-500"
                    x-data
                    x-on:input="
                        $el.style.height = 'auto';
                        $el.style.height = $el.scrollHeight + 'px';
                    "
                    x-on:keydown.cmd.enter="$wire.post()"
                    x-on:keydown.ctrl.enter="$wire.post()"
                ></textarea>
            </div>
        </div>
        <div class="flex items-center justify-between border-t border-gray-100 px-4 py-2.5 dark:border-white/5">
            <div class="text-xs text-gray-400 dark:text-gray-500">
                <span class="hidden sm:inline">Markdown suportado · </span>
                <kbd class="rounded border border-gray-200 px-1 py-0.5 text-[10px] dark:border-white/10"
                    >Ctrl+Enter</kbd
                >
                para postar
            </div>
            <button
                type="submit"
                class="bg-primary-600 hover:bg-primary-500 rounded-lg px-4 py-1.5 text-xs font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
                @disabled (mb_trim($this->content) === '')
            >
                Postar
            </button>
        </div>
    </form>
</div>
