@props([
    'event'/**@var\He4rt\Events\Models\Pivot\EventAttend$participant*/,
    'participant'/**@var\He4rt\Events\Models\EventModel$event*/,
])
<section class="hp-section relative items-start" id="hero">
    <div class="absolute inset-0 -left-[20%] z-0 h-full w-[150%]">
        <img src="{{ asset('images/3pontos/logo-chain.png') }}" alt="" class="h-full w-full object-cover" />

        <div class="bg-elevation-surface/90 absolute inset-0"></div>
        <div class="from-elevation-surface/80 absolute inset-0 bg-gradient-to-t to-transparent"></div>
    </div>

    <div
        class="pointer-events-none absolute top-1/2 right-0 z-5 hidden w-[600px] translate-x-1/3 -translate-y-1/2 xl:block"
    >
        <img
            src="{{ asset('images/3pontos/logo-rounded.svg') }}"
            alt=""
            class="spin-slow h-auto w-full object-contain"
        />
    </div>

    <div class="hp-container relative z-10">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <div>
                @if ($participant)
                    <x-he4rt::headline>
                        <x-slot:badge>
                            <x-he4rt::section-title>Área do Participante</x-he4rt::section-title>
                        </x-slot>
                        <x-slot:title>Parabéns! Sua vaga está garantida!</x-slot>
                        <x-slot:description>
                            Aproveite o evento e não se esqueça de compartilhar nas redes sociais!
                        </x-slot>
                        {{-- TODO: implement this 'shareable' component later --}}
                        @if (1 > 2)
                            <x-slot:actions>
                                <x-he4rt::button>Compartilhar no...</x-he4rt::button>
                                <x-he4rt::button variant="outline">Copiar link</x-he4rt::button>
                            </x-slot>
                        @endif
                    </x-he4rt::headline>
                @else
                    <x-he4rt::headline>
                        <x-slot:badge>
                            <x-he4rt::section-title>Área do Participante</x-he4rt::section-title>
                        </x-slot>
                        <x-slot:title>Garanta seu ingresso!</x-slot>
                        <x-slot:description>
                            Clique no botão abaixo para confirmar sua inscrição no evento.
                        </x-slot>
                        <x-slot:actions>
                            <x-he4rt::button wire:click="eventAttend">Inscrever</x-he4rt::button>
                        </x-slot>
                    </x-he4rt::headline>
                @endif
            </div>

            <div
                x-data="{ visible: false }"
                x-intersect.once="visible = true"
                class="p-2 sm:p-4 lg:p-8 xl:ml-auto xl:min-w-2xl"
            >
                <x-he4rt::animate-block type="blur">
                    <x-he4rt::ticket
                        :user-img="auth()->user()->getFilamentAvatarUrl()"
                        :username="auth()->user()->shortName"
                        :name="auth()->user()->shortName"
                        :github-username="auth()->user()->name"
                        :ticketNumber="$participant?->pivot->attend_order ?? '???????'"
                        githubLink="https://github.com/{{auth()->user()->username}}"
                        :githubText="auth()->user()->username"
                        twitchLink="https://twitch.tv/danielhe4rt"
                        twitchText="danielhe4rt"
                        :eventDate="sprintf('%s às %s', $event->day, $event->start)"
                        eventSubtitle="Ao vivo no Twitch"
                    />
                </x-he4rt::animate-block>
            </div>
        </div>
    </div>
</section>
