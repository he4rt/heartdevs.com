@php
    $events = $this->upcomingEvents;
@endphp

<div>
    @if ($events->isNotEmpty())
        <section class="hp-section" id="agenda">
            <script type="application/ld+json">
                {!! json_encode($this->schemaOrg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) !!}
            </script>

        <div class="hp-container">
            <x-he4rt::headline size="md" align="center" :keywords="['eventos', 'He4rt']" title-tag="h2">
                <x-slot:title>Próximos eventos da comunidade He4rt</x-slot:title>

                <x-slot:description>
                    Reuniões semanais, aulas e encontros gratuitos e abertos para quem está começando ou já
                    atua na área. Participe ao vivo, aprenda programação, tire suas dúvidas e faça networking
                    com outros desenvolvedores — presencial ou online.
                </x-slot:description>
            </x-he4rt::headline>

            <div class="grid w-full max-w-6xl grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $item)
                    @php
                        $event = $item['event'];
                        $occurrence = $item['occurrence'];
                        $cover = $event->getFirstMedia('cover');
                    @endphp

                    <x-he4rt::card class="h-full">
                        @if ($cover)
                            <x-slot:cover>
                                <div
                                    class="h-full w-full"
                                    x-data="{ shown: false }"
                                    x-intersect.threshold.20.once="shown = true"
                                >
                                    <img
                                        src="{{ $cover->getUrl() }}"
                                        alt="Capa do evento {{ $event->title }}"
                                        @if ($cover->width) width="{{ $cover->width }}" @endif
                                        @if ($cover->height) height="{{ $cover->height }}" @endif
                                        loading="lazy"
                                        fetchpriority="low"
                                        class="h-full w-full object-cover opacity-100 transition-opacity duration-1000 ease-in"
                                        :class="shown ? 'opacity-100' : 'opacity-0'"
                                    />
                                </div>
                            </x-slot:cover>
                        @endif
                        <x-slot:icon>
                            <div
                                class="border-primary/16 bg-primary/8 flex h-14 w-14 flex-col items-center justify-center rounded-xl border"
                                aria-hidden="true"
                            >
                                <span class="text-primary text-xs font-bold uppercase">{{ $occurrence->translatedFormat('D') }}</span>
                                <span class="text-text-high text-lg font-extrabold leading-none">{{ $occurrence->format('d') }}</span>
                                <span class="text-text-medium text-[10px] font-semibold uppercase">{{ $occurrence->translatedFormat('M') }}</span>
                            </div>
                        </x-slot:icon>

                        <x-slot:title>{{ $event->title }}</x-slot:title>

                        <x-slot:description>
                            <time class="text-primary font-semibold" datetime="{{ $occurrence->toIso8601String() }}">
                                <x-filament::icon icon="heroicon-o-clock" class="h-4 w-4" />
                                {{ $occurrence->translatedFormat('l, d \d\e F') }} às {{ $occurrence->format('H:i') }}
                            </time>

                            @if ($event->description)
                                <span class="mt-2 block">{{ $event->description }}</span>
                            @endif
                        </x-slot:description>

                        <x-slot:tags>
                            <x-he4rt::tag>
                                {{ $event->category->getLabel() }}
                            </x-he4rt::tag>

                            @if ($event->location)
                                <x-he4rt::tag>
                                    <x-filament::icon icon="heroicon-o-map-pin" class="h-3.5 w-3.5" />
                                    {{ $event->location }}
                                </x-he4rt::tag>
                            @endif
                        </x-slot:tags>

                        <x-slot:actions>
                            <x-he4rt::button
                                :href="$event->external_url ?? 'https://discord.gg/he4rt'"
                                target="_blank"
                                variant="outline"
                                size="sm"
                                icon="heroicon-s-chevron-right"
                            >
                                Participar
                            </x-he4rt::button>
                        </x-slot:actions>
                    </x-he4rt::card>
                @endforeach
            </div>
        </div>
    </section>
    @endif
</div>
