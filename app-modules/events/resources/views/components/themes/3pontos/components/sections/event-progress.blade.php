@php
    $profileData = [
        'name' => 'NextuRhe4rt',
        'level' => 1,
        'exp_current' => 33,
        'exp_total' => 100,
    ];

    $expPercent = floor(($profileData['exp_current'] / $profileData['exp_total']) * 100);
@endphp

<section class="hp-section relative" id="social-action">
    <div
        class="absolute bottom-0 left-0 z-1 flex origin-top rotate-90 justify-start sm:-translate-x-[20%] sm:translate-y-32 lg:-translate-x-[5%] lg:translate-y-24 lg:rotate-0"
    >
        <img
            src="{{ asset('images/3pontos/logo-chain.png') }}"
            alt=""
            class="hidden h-auto w-full object-contain sm:block sm:max-w-[80%]"
        />
    </div>

    <div class="hp-container relative z-10">
        <div class="grid grid-cols-1 items-start gap-x-12 lg:grid-cols-[1fr_5fr]">
            <div
                x-data="{ visible: false }"
                x-intersect.once="visible = true"
                class="mb-4 flex items-center justify-center sm:justify-start"
            >
                <x-he4rt::animate-block duration="700">
                    <x-he4rt::section-title size="lg">Prova Social</x-he4rt::section-title>
                </x-he4rt::animate-block>
            </div>

            <div class="flex flex-col gap-20">
                <div>
                    <x-he4rt::headline class="mx-0">
                        <x-slot:title>Seu progresso no evento</x-slot>
                        <x-slot:description>
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent vestibulum a augue at
                            ornare.
                        </x-slot>
                    </x-he4rt::headline>
                </div>

                <div
                    x-data="{ visible: false }"
                    x-intersect.threshold.20.once="visible = true"
                    class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_1.2fr]"
                >
                    <x-he4rt::animate-block>
                        <x-he4rt::card :interactive="false">
                            <div class="grid grid-cols-1 gap-8 px-4 py-6 sm:grid-cols-[auto_1fr_auto]">
                                <div class="flex flex-col items-center justify-center">
                                    <x-he4rt::avatar size="xl" />
                                </div>

                                <div class="flex flex-col items-center justify-center gap-4">
                                    <div class="flex w-full justify-between gap-4">
                                        <x-he4rt::heading size="2xs">
                                            {{ $profileData['name'] }}
                                        </x-he4rt::heading>

                                        <x-he4rt::text size="sm" class="mt-0.5">
                                            Nível {{ sprintf('%02d', $profileData['level']) }}
                                        </x-he4rt::text>
                                    </div>

                                    <div
                                        class="bg-outline-dark relative h-2 w-full overflow-hidden rounded-full"
                                        style="--p: {{ $expPercent }}%"
                                    >
                                        <div
                                            class="bg-outline-light absolute top-0 left-0 h-full rounded-full"
                                            style="width: var(--p)"
                                        ></div>
                                    </div>

                                    <div class="flex w-full justify-between gap-4">
                                        <x-he4rt::text size="sm" class="mt-0.5">
                                            Exp {{ $profileData['exp_current'] }}/{{ $profileData['exp_total'] }}
                                        </x-he4rt::text>

                                        <x-he4rt::text size="sm" class="mt-0.5">{{ $expPercent }}%</x-he4rt::text>
                                    </div>
                                </div>

                                <div class="flex flex-col items-center gap-4">
                                    <x-he4rt::button rounded="sm" size="xs" class="text-helper-error bg-transparent">
                                        Desconectar conta
                                    </x-he4rt::button>
                                    <x-he4rt::button rounded="sm" size="xs" block>Deslogar</x-he4rt::button>
                                </div>
                            </div>
                        </x-he4rt::card>
                    </x-he4rt::animate-block>

                    <x-he4rt::animate-block>
                        <x-he4rt::card :interactive="false" class="flex-row gap-8">
                            <div class="grid w-full grid-cols-1 gap-8 px-4 py-6 lg:grid-cols-2">
                                <div
                                    class="grid grid-cols-1 items-center justify-between gap-8 sm:grid-cols-[auto_1fr_auto]"
                                >
                                    <div class="flex flex-col items-center justify-center">
                                        <x-he4rt::avatar size="xl" />
                                    </div>
                                    <div class="flex flex-col items-center justify-center gap-2 sm:items-start">
                                        <x-he4rt::heading size="2xs">Twitch</x-he4rt::heading>
                                        <x-he4rt::text size="sm" class="mt-0.5">0 Mensagens</x-he4rt::text>
                                    </div>
                                    <div>
                                        <x-he4rt::button rounded="sm" size="xs">Desconectar</x-he4rt::button>
                                    </div>
                                </div>
                                <div
                                    class="grid grid-cols-1 items-center justify-between gap-8 sm:grid-cols-[auto_1fr_auto]"
                                >
                                    <div class="flex flex-col items-center justify-center">
                                        <x-he4rt::avatar size="xl" />
                                    </div>
                                    <div class="flex flex-col items-center justify-center gap-2 sm:items-start">
                                        <x-he4rt::heading size="2xs">Discord</x-he4rt::heading>
                                        <x-he4rt::text size="sm" class="mt-0.5">0 Mensagens</x-he4rt::text>
                                    </div>
                                    <div>
                                        <x-he4rt::button rounded="sm" size="xs">Desconectar</x-he4rt::button>
                                    </div>
                                </div>
                            </div>
                        </x-he4rt::card>
                    </x-he4rt::animate-block>
                </div>
            </div>
        </div>
    </div>
</section>
