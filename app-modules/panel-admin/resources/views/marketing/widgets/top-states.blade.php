<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ __('panel-admin::location.top.heading') }}</x-slot>
        <x-slot name="description">{{ __('panel-admin::location.top.hint') }}</x-slot>

        <div class="flex flex-col gap-4">
            @forelse ($top as $index => $row)
                <div class="flex items-center gap-3">
                    <span class="w-5 font-mono text-xs font-bold text-zinc-400 tabular-nums">
                        {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                    </span>
                    <div class="flex-1">
                        <div class="mb-1 flex items-baseline justify-between gap-2">
                            <span class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $row->name }}</span>
                            <span class="text-xs text-zinc-400 tabular-nums">{{ number_format($row->members) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/10">
                            <div
                                class="h-full rounded-full"
                                style="width: {{ $row->members > 0 ? round($row->members / $top[0]->members * 100) : 0 }}%; background: #7c3aed;"
                            ></div>
                        </div>
                    </div>
                    <span class="w-12 text-right text-sm font-bold text-zinc-950 tabular-nums dark:text-white">
                        {{ number_format($row->share, 1) }}%
                    </span>
                </div>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('panel-admin::location.top.empty') }}</p>
            @endforelse

            @if ($statesReached > count($top))
                <div class="mt-2 flex items-center justify-between border-t border-dashed border-zinc-200 pt-4 text-sm text-zinc-500 dark:border-white/10 dark:text-zinc-400">
                    <span>{{ __('panel-admin::location.top.others', ['count' => $statesReached - count($top)]) }}</span>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
