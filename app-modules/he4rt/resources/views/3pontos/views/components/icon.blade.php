@props([
    'icon' => 'heroicon-s-heart',
])

<div {{ $attributes->class(['bg-icon-high rounded-md p-2']) }}>
    <x-filament::icon :icon="$icon" class="text-icon-dark h-6 w-6" />
</div>
