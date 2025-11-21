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

<div class="border-outline-dark mt-6 border-b px-4 pb-3 transition-all duration-300 ease-in-out hover:px-5">
    <div class="grid grid-cols-2 gap-x-4 gap-y-4 lg:grid-cols-[auto_1fr_auto]">
        <div class="flex items-center gap-2 lg:order-1">
            <x-he4rt::icon size="md" class="text-icon-light bg-transparent p-0!" icon="heroicon-o-clock" />
            <x-he4rt::text size="xs" class="font-semibold">{{ $startsAt }}</x-he4rt::text>
        </div>

        <div class="flex items-center justify-end gap-2 lg:order-3 lg:justify-center">
            <x-he4rt::icon size="md" :icon="$config['icon']" class="{{ $config['color'] }} bg-transparent p-0!" />
            <x-he4rt::text size="xs" class="{{ $config['color'] }} font-semibold">
                {{ $config['text'] }}
            </x-he4rt::text>
        </div>

        <div class="col-span-2 flex items-center gap-2 lg:order-2 lg:col-span-1">
            <x-he4rt::heading size="2xs">
                {{ $title }}
            </x-he4rt::heading>
        </div>
    </div>
</div>
