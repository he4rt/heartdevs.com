@props([
    'event',
    'speakers' => $event->speakers->where('name',
    '!=',
    $event->slug),
])
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
            class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:gap-8 lg:grid-cols-4"
        >
            @forelse ($speakers as $speaker)
                <x-he4rt::animate-block>
                    <x-he4rt::card class="hover:border-b-outline-light border-b-8">
                        <x-slot:header class="flex flex-col items-center justify-center border-none pb-0">
                            <div class="flex flex-col items-center gap-2">
                                <x-he4rt::avatar size="2xl" src="{{$speaker->getFirstMediaUrl('avatar')}}" />
                                <x-he4rt::heading level="3" size="xs">{{ $speaker->name }}</x-he4rt::heading>
                                <x-he4rt::text class="text-center" size="sm">
                                    {{ $speaker->talks->first()->field_type }}
                                </x-he4rt::text>
                            </div>
                        </x-slot>
                        <x-slot:description class="pb-4 text-center">
                            {{ $speaker->talks->first()->description }}
                        </x-slot>
                    </x-he4rt::card>
                </x-he4rt::animate-block>
            @empty
                <p>There is no Speaker Yet.</p>
            @endforelse
        </div>
    </div>
</section>
