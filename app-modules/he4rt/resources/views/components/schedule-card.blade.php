@props([
    'startsAt' => '14:00',
    'title' => 'Networking',
    'status' => 'finished',
])

@php
    $config = match ($status) {
        'finished' => [
            'color' => 'text-green-300',
            'text' => 'Finalizado',
            'icon' => 'heroicon-s-check-circle',
        ],
        'in_progress' => [
            'color' => 'text-orange-300',
            'text' => 'Em andamento',
            'icon' => 'heroicon-s-clock',
        ],
        'upcoming' => [
            'color' => 'text-blue-300',
            'text' => 'Em breve',
            'icon' => 'heroicon-o-calendar',
        ],
    };
@endphp

<div class="border-outline-dark mt-6 border-b px-4 pb-3">
    <div class="flex items-center justify-between">
        <div class="flex items-center justify-center gap-3">
            <x-filament::icon icon="heroicon-o-clock" class="text-icon-high h-5 w-5" />
            <span class="text-text-medium text-xs font-semibold">{{ $startsAt }}</span>
            <x-he4rt::heading>
                {{ $title }}
            </x-he4rt::heading>
        </div>

        <div class="flex items-center justify-center gap-2">
            <x-filament::icon :icon="$config['icon']" class="w-5 h-5 {{ $config['color'] }}" />
            <span class="{{ $config['color'] }} font-semibold">
                {{ $config['text'] }}
            </span>
        </div>
    </div>
</div>
