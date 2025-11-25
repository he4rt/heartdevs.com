@php
    $socials = [
        [
            'icon' => 'fab-instagram',
            'link' => 'https://www.instagram.com/3pontos.hub/',
        ],
        [
            'icon' => 'fab-square-facebook',
            'link' => 'https://www.facebook.com/profile.php?id=61582825820628',
        ],
        [
            'icon' => 'fab-x-twitter',
            'link' => 'https://x.com/3Pontoshub',
        ],
    ];
@endphp

<section class="hp-section relative z-1" id="info">
    <div class="hp-container">
        <div class="grid grid-cols-1 gap-y-8 lg:grid-cols-2">
            <div class="relative h-full w-full">
                <img
                    src="{{ asset('images/3pontos/3pontos-map.png') }}"
                    alt="map"
                    class="h-full w-full rounded-lg object-cover"
                />
                <div class="from-elevation-surface/10 absolute inset-0 bg-gradient-to-t to-transparent"></div>
            </div>
            <div class="flex flex-col items-center justify-center lg:pl-16">
                <x-he4rt::headline>
                    <x-slot:title>Informações sobre o evento</x-slot>
                    <x-slot:subtitle class="gap-8">
                        <div class="flex flex-col items-center gap-2 sm:flex-row">
                            <x-he4rt::icon
                                :interactive="false"
                                icon="heroicon-o-map-pin"
                                class="text-icon-light w-fit bg-transparent p-0!"
                            />
                            <x-he4rt::text class="font-bold">
                                Endereço: Alameda Santos, 1165 – Jardim Paulista, São Paulo
                            </x-he4rt::text>
                        </div>
                        <div class="flex flex-col items-center gap-2 sm:flex-row">
                            <x-he4rt::icon
                                :interactive="false"
                                icon="heroicon-o-clock"
                                class="text-icon-light w-fit bg-transparent p-0!"
                            />
                            <x-he4rt::text class="font-bold">
                                Horário: Sábado (3/12) - 14:00 até 19:00 (Chegar às 14h30)
                            </x-he4rt::text>
                        </div>
                        <div class="flex justify-center gap-8 sm:justify-start">
                            @foreach ($socials as $social)
                                <x-he4rt::icon
                                    rel="noopener noreferrer"
                                    target="_blank"
                                    :href="$social['link']"
                                    :icon="$social['icon']"
                                    class="text-icon-light border-none bg-transparent p-0"
                                />
                            @endforeach
                        </div>
                    </x-slot>
                    <x-slot:description>
                        Um evento que vai dar start na forma como as comunidades existem e se relacionam, cheio de
                        palestras interessantes e assuntos atuais para debater. Não perca essa chance!
                    </x-slot>
                    <x-slot:actions>
                        <x-he4rt::button
                            rel="noopener noreferrer"
                            target="_blank"
                            href="https://www.google.com/maps/dir//Alameda+Santos,+1165+-+Jardim+Paulista,+S%C3%A3o+Paulo+-+SP,+01419-002/@0,0,22z/data=!4m6!4m5!1m0!1m2!1m1!1s0x94ce59c637655555:0x70bd59e2f5bca37!3e0?gl=br&g_ep=Eg1tbF8yMDI1MTExOV8wIJvbDyoASAJQAQ%3D%3D"
                        >
                            Como chegar
                        </x-he4rt::button>
                    </x-slot>
                </x-he4rt::headline>
            </div>
        </div>
    </div>
</section>
