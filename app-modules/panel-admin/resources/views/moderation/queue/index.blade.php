<div wire:poll.30s class="flex flex-col gap-3" style="height: calc(100vh - 200px)">
    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
        <flux:select
            wire:model.live="statusFilter"
            size="sm"
            class="dark:[&>option]:!bg-zinc-800 dark:[&>option]:!text-zinc-100 w-36 !shadow-none dark:!border-white/10 dark:!bg-white/5"
        >
            <flux:select.option value="all">
                {{ __('panel-admin::moderation.queue.filters.all') }} — {{ __('panel-admin::moderation.queue.filters.status') }}</flux:select.option
            >
            @foreach (\He4rt\Moderation\Enums\CaseStatus::cases() as $status)
                <flux:select.option value="{{ $status->value }}">{{ $status->getLabel() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select
            wire:model.live="platformFilter"
            size="sm"
            class="dark:[&>option]:!bg-zinc-800 dark:[&>option]:!text-zinc-100 w-36 !shadow-none dark:!border-white/10 dark:!bg-white/5"
        >
            <flux:select.option value="all">
                {{ __('panel-admin::moderation.queue.filters.all') }} — {{ __('panel-admin::moderation.queue.filters.platform') }}</flux:select.option
            >
            @foreach (\He4rt\Moderation\Enums\Platform::cases() as $platform)
                <flux:select.option value="{{ $platform->value }}">{{ $platform->getLabel() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select
            wire:model.live="violationFilter"
            size="sm"
            class="dark:[&>option]:!bg-zinc-800 dark:[&>option]:!text-zinc-100 w-36 !shadow-none dark:!border-white/10 dark:!bg-white/5"
        >
            <flux:select.option value="all">
                {{ __('panel-admin::moderation.queue.filters.all') }} — {{ __('panel-admin::moderation.queue.filters.violation') }}</flux:select.option
            >
            @foreach (\He4rt\Moderation\Enums\ViolationType::cases() as $violation)
                <flux:select.option value="{{ $violation->value }}">{{ $violation->getLabel() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select
            wire:model.live="severityFilter"
            size="sm"
            class="dark:[&>option]:!bg-zinc-800 dark:[&>option]:!text-zinc-100 w-36 !shadow-none dark:!border-white/10 dark:!bg-white/5"
        >
            <flux:select.option value="all">
                {{ __('panel-admin::moderation.queue.filters.all') }} — {{ __('panel-admin::moderation.queue.filters.severity') }}</flux:select.option
            >
            @foreach (\He4rt\Moderation\Enums\Severity::cases() as $severity)
                <flux:select.option value="{{ $severity->value }}">{{ $severity->getLabel() }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="ml-auto flex items-center gap-2">
            <span
                class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 tabular-nums dark:bg-white/10 dark:text-zinc-300"
            >
                {{ $this->cases->count() }}
                <span
                    class="text-zinc-400 dark:text-zinc-500"
                    >{{ __('panel-admin::moderation.queue.cases_label') }}</span
                >
            </span>
        </div>
    </div>

    {{-- Inbox split panel --}}
    <div
        class="flex flex-1 overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-xs dark:border-white/5 dark:bg-white/[0.02] dark:shadow-none"
    >
        {{-- Left: case list --}}
        <div class="flex w-[35%] max-w-[440px] min-w-[340px] flex-col border-r border-zinc-200/80 dark:border-white/5">
            <div
                class="flex items-center justify-between border-b border-zinc-200/80 bg-zinc-50/50 px-5 py-2.5 dark:border-white/5 dark:bg-white/[0.03]"
            >
                <span class="text-xs font-semibold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">
                    {{
                        __('panel-admin::moderation.queue.cases_count', [
                            'count' => $this->cases->count(),
                        ])
                    }}
                </span>
                <span class="text-[10px] font-medium tracking-wider text-zinc-400 uppercase dark:text-zinc-500">
                    {{ __('panel-admin::moderation.queue.priority_sort') }}
                </span>
            </div>

            <div
                class="flex-1 overflow-y-auto [scrollbar-color:theme(colors.zinc.300)_transparent] [scrollbar-width:thin] dark:[scrollbar-color:theme(colors.zinc.700)_transparent]"
            >
                @forelse ($this->cases as $case)
                    @include ('panel-admin::moderation.queue.case-list-item', ['case' => $case])
                @empty
                    <div class="flex flex-col items-center justify-center gap-3 p-12">
                        <div class="rounded-full bg-zinc-100 p-3 dark:bg-white/5">
                            <x-heroicon-o-inbox class="size-6 text-zinc-400 dark:text-zinc-500" />
                        </div>
                        <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('panel-admin::moderation.queue.no_cases') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Right: case detail --}}
        <div
            class="flex-1 overflow-y-auto bg-zinc-50/50 [scrollbar-color:theme(colors.zinc.300)_transparent] [scrollbar-width:thin] dark:bg-white/[0.02] dark:[scrollbar-color:theme(colors.zinc.700)_transparent]"
        >
            @if ($this->selectedCase)
                @include ('panel-admin::moderation.queue.case-detail', ['case' => $this->selectedCase])
            @else
                <div class="flex h-full flex-col items-center justify-center gap-3">
                    <div class="rounded-full bg-zinc-100 p-4 dark:bg-white/5">
                        <x-heroicon-o-inbox-stack class="size-8 text-zinc-300 dark:text-zinc-600" />
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('panel-admin::moderation.queue.no_case_selected') }}</p>
                        <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{{
                            __(
                                'panel-admin::moderation.queue.no_case_selected_subtitle',
                            )
                        }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</div>
