@props([
    'startsAt',
    'endsAt',
    'title',
    'icon',
    'speakers',
])

@php
    /** @var \Carbon\Carbon $startsAt */
    /** @var \Carbon\Carbon $endsAt */

    $config = match (true) {
        $endsAt->isPast() => [
            'color' => 'text-green-300',
            'text' => 'Finalizado',
            'icon' => 'heroicon-s-check-circle',
        ],
        default => [
            'color' => 'text-orange-300',
            'text' => 'Em andamento',
            'icon' => 'heroicon-s-clock',
        ],
        $startsAt->isFuture() => [
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
            <x-he4rt::text size="xs" class="font-semibold">{{ $startsAt->format('H:i') }}</x-he4rt::text>
        </div>

        <div class="flex items-center justify-end gap-2 lg:order-3 lg:justify-center">
            <x-he4rt::icon size="md" :icon="$config['icon']" class="{{ $config['color'] }} bg-transparent p-0!" />
            <x-he4rt::text size="xs" class="{{ $config['color'] }} font-semibold">
                {{ $config['text'] }}
            </x-he4rt::text>
        </div>

        <div class="col-span-2 flex items-center gap-2 lg:order-2 lg:col-span-1">
            <x-he4rt::heading size="2xs" class="flex gap-2">
                <x-he4rt::icon size="md" :icon="$icon" class="text-icon-light bg-transparent p-0!" />
                <div class="flex gap-3">
                    {{ $title }}
                    @if ($speakers)
                        <span class="text-text-medium">// {{ $speakers->name ?? '' }} \\</span>
                    @endif
                </div>
            </x-he4rt::heading>
        </div>
    </div>
</div>
