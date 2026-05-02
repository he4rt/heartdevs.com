<div
    wire:poll.30s
    x-data="{ showDetail: window.innerWidth >= 1024 || false }"
    class="flex flex-col gap-3"
    style="height: calc(100vh - 200px)"
>
    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
        <flux:select
            wire:model.live="statusFilter"
            size="sm"
            class="dark:[&>option]:!bg-zinc-800 dark:[&>option]:!text-zinc-100 w-40 !shadow-none dark:!border-white/10 dark:!bg-white/5"
        >
            <flux:select.option value="all">
                {{ __('panel-admin::moderation.appeal_queue.filters.all') }} — {{ __('panel-admin::moderation.appeal_queue.filters.status') }}</flux:select.option
            >
            @foreach (\He4rt\Moderation\Enums\AppealStatus::cases() as $status)
                <flux:select.option value="{{ $status->value }}">{{ $status->getLabel() }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="ml-auto flex items-center gap-2">
            <span
                class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 tabular-nums dark:bg-white/10 dark:text-zinc-300"
            >
                {{ $this->appeals->count() }}
                <span class="text-zinc-400 dark:text-zinc-500">{{
                    __(
                        'panel-admin::moderation.appeal_queue.appeals_label',
                    )
                }}</span>
            </span>
        </div>
    </div>

    {{-- Inbox split panel --}}
    <div
        class="flex flex-1 overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-xs dark:border-white/5 dark:bg-white/[0.02] dark:shadow-none"
    >
        {{-- Left: appeal list --}}
        <div
            x-show="!showDetail || window.innerWidth >= 1024"
            x-cloak
            class="flex w-full flex-col border-r border-zinc-200/80 lg:w-[35%] lg:max-w-[440px] lg:min-w-[340px] dark:border-white/5"
        >
            <div
                class="flex items-center justify-between border-b border-zinc-200/80 bg-zinc-50/50 px-5 py-2.5 dark:border-white/5 dark:bg-white/[0.03]"
            >
                <span class="text-xs font-semibold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">
                    {{
                        __('panel-admin::moderation.appeal_queue.appeals_count', [
                            'count' => $this->appeals->count(),
                        ])
                    }}
                </span>
                <span class="text-[10px] font-medium tracking-wider text-zinc-400 uppercase dark:text-zinc-500">
                    {{ __('panel-admin::moderation.appeal_queue.sla_sort') }}
                </span>
            </div>

            <div
                class="flex-1 overflow-y-auto [scrollbar-color:theme(colors.zinc.300)_transparent] [scrollbar-width:thin] dark:[scrollbar-color:theme(colors.zinc.700)_transparent]"
            >
                @forelse ($this->appeals as $appeal)
                    <div x-on:click="showDetail = true">
                        @include ('panel-admin::moderation.appeal-queue.appeal-list-item', ['appeal' => $appeal])
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center gap-3 p-12">
                        <div class="rounded-full bg-zinc-100 p-3 dark:bg-white/5">
                            <x-heroicon-o-scale class="size-6 text-zinc-400 dark:text-zinc-500" />
                        </div>
                        <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('panel-admin::moderation.appeal_queue.no_appeals') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Right: appeal detail --}}
        <div
            x-show="showDetail || window.innerWidth >= 1024"
            x-cloak
            class="w-full flex-1 overflow-y-auto bg-zinc-50/50 [scrollbar-color:theme(colors.zinc.300)_transparent] [scrollbar-width:thin] dark:bg-white/[0.02] dark:[scrollbar-color:theme(colors.zinc.700)_transparent]"
        >
            @if ($this->selectedAppeal)
                {{-- Mobile back button --}}
                <div
                    class="sticky top-0 z-10 flex items-center gap-2 border-b border-zinc-200/80 bg-zinc-50/80 px-4 py-2 backdrop-blur-sm lg:hidden dark:border-white/5 dark:bg-zinc-900/80"
                >
                    <button
                        type="button"
                        x-on:click="showDetail = false"
                        class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-zinc-600 transition hover:bg-zinc-200/50 dark:text-zinc-400 dark:hover:bg-white/5"
                    >
                        <x-heroicon-o-arrow-left class="size-3.5" />
                        {{
                            __(
                                'panel-admin::moderation.appeal_queue.appeals_label',
                            )
                        }}
                    </button>
                </div>
                @include ('panel-admin::moderation.appeal-queue.appeal-detail', ['appeal' => $this->selectedAppeal])
            @else
                <div class="flex h-full flex-col items-center justify-center gap-3">
                    <div class="rounded-full bg-zinc-100 p-4 dark:bg-white/5">
                        <x-heroicon-o-scale class="size-8 text-zinc-300 dark:text-zinc-600" />
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{
                            __(
                                'panel-admin::moderation.appeal_queue.no_appeal_selected',
                            )
                        }}</p>
                        <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{{
                            __(
                                'panel-admin::moderation.appeal_queue.no_appeal_selected_subtitle',
                            )
                        }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</div>
