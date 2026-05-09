<x-filament-panels::page>
    <div class="mx-auto w-full max-w-4xl space-y-4">
        {{-- Back link --}}
        <a
            href="{{ \He4rt\PanelApp\Pages\TimelinePage::getUrl() }}"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
        >
            <x-heroicon-o-arrow-left class="h-4 w-4" />
            Voltar para Timeline
        </a>

        {{-- Original post --}}
        <livewire:timeline-post-show
            :timeline-id="$this->record"
            :show-replies="false"
            :key="'thread-root-' . $this->record"
        />

        {{-- Reply composer --}}
        <livewire:timeline-reply-composer :timeline-id="$this->record" :key="'reply-composer-' . $this->record" />

        {{-- All replies --}}
        <livewire:timeline-thread-replies :timeline-id="$this->record" :key="'thread-replies-' . $this->record" />
    </div>
</x-filament-panels::page>
