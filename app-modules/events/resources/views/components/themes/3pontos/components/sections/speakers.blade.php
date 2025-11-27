@props([
    'event',
    'speakers' => $event->speakers->where('name',
    '!=',
    $event->slug),
])

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

<section class="hp-section relative" id="speakers">
    <div
        class="absolute bottom-0 left-0 z-1 flex origin-top rotate-90 justify-start sm:-translate-x-[20%] sm:translate-y-32 lg:-translate-x-[5%] lg:translate-y-24 lg:rotate-0 xl:translate-y-0"
    >
        <img
            src="{{ asset('images/3pontos/logo-chain.png') }}"
            alt=""
            class="hidden h-auto w-full object-contain sm:block sm:max-w-[60%]"
        />
    </div>

    <div class="hp-container relative z-10">
        <div>
            <x-he4rt::headline align="center">
                <x-slot:badge>
                    <x-he4rt::section-title>Palestrantes</x-he4rt::section-title>
                </x-slot>
                <x-slot:title>Palestrantes do evento</x-slot>
            </x-he4rt::headline>
        </div>

        <div
            x-data="{ visible: false }"
            x-intersect.once="visible = true"
            class="grid max-w-7xl grid-cols-1 gap-6 sm:grid-cols-2 sm:gap-8 lg:grid-cols-3"
        >
            @forelse ($speakers as $speaker)
                <x-he4rt::animate-block>
                    <x-he4rt::card
                        class="bg-elevation-01dp/32 group hover:border-b-outline-light relative h-[16rem] overflow-hidden border-b-8 transition-all duration-500 hover:gap-2"
                    >
                        <x-slot:header class="border-none pb-0">
                            <div class="relative w-full transition-all duration-500 ease-in-out">
                                <div
                                    class="absolute top-0 transition-all duration-500 ease-in-out group-hover:left-0 group-hover:scale-75"
                                >
                                    <x-he4rt::avatar size="2xl" src="{{$speaker->getFirstMediaUrl('avatar')}}" />
                                </div>

                                <div
                                    class="flex min-h-20 flex-col gap-y-2 pt-24 text-left transition-all duration-500 ease-in-out group-hover:scale-90 group-hover:justify-center group-hover:pt-0 group-hover:pl-20"
                                >
                                    <x-he4rt::heading level="3" size="xs">
                                        {{ $speaker->name }}
                                    </x-he4rt::heading>

                                    <x-he4rt::text class="transition-opacity duration-300 group-hover:opacity-80">
                                        {{ $speaker->talks->first()->field_type }}
                                    </x-he4rt::text>
                                </div>
                            </div>
                        </x-slot>
                        <div class="custom-scrollbar h-full overflow-hidden transition-all duration-500 ease-in-out">
                            <x-he4rt::text class="line-clamp-1 transition-all duration-500 group-hover:line-clamp-none">
                                {{ $speaker->talks->first()->description }}
                            </x-he4rt::text>
                        </div>

                        <div class="hidden gap-x-8 group-hover:flex">
                            @foreach ($socials as $social)
                                <x-he4rt::icon
                                    rel="noopener noreferrer"
                                    target="_blank"
                                    size="sm"
                                    :href="$social['link']"
                                    :icon="$social['icon']"
                                    class="text-icon-light h-full border-none bg-transparent p-0"
                                />
                            @endforeach
                        </div>
                    </x-he4rt::card>
                </x-he4rt::animate-block>
            @empty
                <p>There is no Speaker Yet.</p>
            @endforelse
        </div>
    </div>
</section>
