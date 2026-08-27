@php
    $photos = [
        ['src' => 'pub-1.jpg', 'alt' => 'Grupo da comunidade He4rt reunido em um encontro presencial'],
        ['src' => 'pub-2.jpg', 'alt' => 'Camisas da comunidade He4rt em um encontro presencial'],
        ['src' => 'pub-3.jpg', 'alt' => 'Palestra informal durante o encontro da comunidade He4rt'],
    ];
@endphp

<section class="hp-section" id="comunidade-em-acao">
    <style>
        @keyframes community-marquee {
            to {
                transform: translateX(-50%);
            }
        }
    </style>

    <div class="hp-page hp-container">
        <x-he4rt::headline align="center" size="md" :keywords="['ação']">
            <x-slot:badge>
                <x-he4rt::badge>
                    <x-filament::icon icon="heroicon-o-camera" class="h-5 w-5" />
                    Bastidores
                </x-he4rt::badge>
            </x-slot>

            <x-slot:title>A comunidade em ação</x-slot>

            <x-slot:description>
                Encontros, mentorias e conversas de bastidor: registros de quem faz a He4rt acontecer fora da tela.
            </x-slot>
        </x-he4rt::headline>

        <div
            class="w-full overflow-hidden mask-[linear-gradient(to_right,transparent,black_10%,black_90%,transparent)] [-webkit-mask-image:linear-gradient(to_right,transparent,black_10%,black_90%,transparent)]"
        >
            <div
                class="flex w-max animate-[community-marquee_26s_linear_infinite] gap-4 hover:[animation-play-state:paused] motion-reduce:animate-none sm:gap-6"
            >
                @foreach ([...$photos, ...$photos] as $photo)
                    <img
                        src="{{ asset('images/community/' . $photo['src']) }}"
                        alt="{{ $photo['alt'] }}"
                        loading="lazy"
                        decoding="async"
                        class="border-outline-low h-48 w-auto shrink-0 rounded-lg border object-cover sm:h-60"
                    />
                @endforeach
            </div>
        </div>
    </div>
</section>
