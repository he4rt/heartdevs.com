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
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center justify-center gap-3">
            <x-he4rt::icon size="md" class="text-icon-light bg-transparent p-0!" icon="heroicon-o-clock" />
            <x-he4rt::text size="xs" class="font-semibold">{{ $startsAt }}</x-he4rt::text>
            <x-he4rt::heading size="2xs">
                {{ $title }}
            </x-he4rt::heading>
        </div>

        <div class="flex items-center justify-center gap-2">
            <x-he4rt::icon size="md" :icon="$config['icon']" class="{{ $config['color'] }} bg-transparent p-0!" />
            <x-he4rt::text size="xs" class="{{ $config['color'] }} font-semibold">
                {{ $config['text'] }}
            </x-he4rt::text>
        </div>
    </div>
</div>
