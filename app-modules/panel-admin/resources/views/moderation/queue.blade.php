<x-filament-panels::page
    x-on:mount-queue-action.window="$wire.mountAction($event.detail.action, $event.detail.arguments ?? {})"
>
    <livewire:moderation-queue />
</x-filament-panels::page>
