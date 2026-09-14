<x-filament-panels::page>
    <div class="mx-auto w-full max-w-4xl space-y-4">
        <a
            href="{{ \He4rt\PanelApp\Pages\EventsPage::getUrl() }}"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
        >
            <x-heroicon-o-arrow-left class="h-4 w-4" />
            {{ __('events::pages.back_to_events') }}
        </a>

        <livewire:event-detail :event-id="$this->record" :key="'event-detail-' . $this->record" />
    </div>
</x-filament-panels::page>
