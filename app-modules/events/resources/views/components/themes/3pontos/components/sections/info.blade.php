@props([
    'event',
])
<section class="hp-section relative z-1" id="info">
    <div class="hp-container">
        <div class="grid grid-cols-1 gap-y-8 lg:grid-cols-2">
            <div class="relative h-full w-full">
                <img src="{{ asset('images/3pontos/3pontos-map.png') }}" alt="map" />
                <div class="from-elevation-surface/10 absolute inset-0 bg-gradient-to-t to-transparent"></div>
            </div>
            <div class="flex flex-col items-center justify-center lg:pl-16">
                <x-he4rt::headline>
                    <x-slot:title>Informações sobre o evento</x-slot>
                    <x-slot:subtitle class="gap-8">
                        <div class="flex gap-2">
                            <x-he4rt::icon
                                :interactive="false"
                                icon="heroicon-o-map-pin"
                                class="text-icon-light bg-transparent p-0!"
                            />
                            <x-he4rt::text class="font-bold">Endereço: {{ $event->location }}</x-he4rt::text>
                        </div>
                        <div class="flex gap-2">
                            <x-he4rt::icon
                                :interactive="false"
                                icon="heroicon-o-clock"
                                class="text-icon-light bg-transparent p-0!"
                            />
                            <x-he4rt::text class="font-bold">
                                Horário: {{ $event->start_at->format('D') }} {{ $event->day }} - {{ $event->start }}
                                até {{ $event->end }}
                            </x-he4rt::text>
                        </div>
                        <div class="flex gap-8">
                            <x-he4rt::icon icon="fab-instagram" class="text-icon-light bg-transparent p-0!" />
                            <x-he4rt::icon icon="fab-x-twitter" class="text-icon-light bg-transparent p-0!" />
                            <x-he4rt::icon icon="fab-linkedin" class="text-icon-light bg-transparent p-0!" />
                        </div>
                    </x-slot>
                    <x-slot:description>
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur lobortis molestie blandit.
                        Nunc iaculis purus sollicitudin laoreet dapibus
                    </x-slot>
                    <x-slot:actions>
                        <x-he4rt::button>Button</x-he4rt::button>
                    </x-slot>
                </x-he4rt::headline>
            </div>
        </div>
    </div>
</section>
