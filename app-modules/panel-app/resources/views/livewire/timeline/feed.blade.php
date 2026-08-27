<div>
    <livewire:timeline-composer />

    <section class="mt-4 space-y-4">
        @forelse ($items as $item)
            <div wire:key="tl-{{ $item->id }}">
                @if ($item->postable_type === 'moderation_event')
                    <x-panel-app::timeline.moderation-event :timeline="$item" />
                @else
                    <livewire:timeline-post-show :timeline-id="$item->id" :key="'post-' . $item->id" />
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 p-8 text-center dark:border-white/10">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('panel-app::feed.empty') }}</p>
            </div>
        @endforelse

        @if ($items->hasMorePages())
            <div x-data x-intersect="$wire.loadMore()" class="flex justify-center py-4">
                <x-filament::loading-indicator class="h-5 w-5" />
            </div>
        @endif
    </section>
</div>
