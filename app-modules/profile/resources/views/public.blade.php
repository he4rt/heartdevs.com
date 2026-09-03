@php
    $facts = array_filter([
        'Senioridade' => $profile->seniority,
        'Experiência' => $profile->yearsExperience
            ? $profile->yearsExperience . ($profile->yearsExperience === 1 ? ' ano' : ' anos')
            : null,
        'Disponibilidade' => $profile->startAvailability,
    ]);

    $workPreferences = array_filter([
        $profile->openToRemote ? 'Aberto a remoto' : null,
        $profile->willingToRelocate ? 'Disposto a mudar de cidade' : null,
        ...$profile->employmentTypes,
    ]);

    $links = [...$profile->socialLinks, ...$profile->connectedAccounts];

    $hasBody = $profile->about || $profile->skills !== [] || $profile->experiences !== [] || $profile->projects !== [];
    $hasAside = (bool) $profile->level || $profile->badges !== [];

    $card = 'border-outline-low bg-elevation-01dp/60 rounded-3xl border p-6 backdrop-blur-sm sm:p-7';
    $sectionHeading = 'text-text-high flex items-center gap-2.5 text-xs font-semibold tracking-[0.14em] uppercase';
    $sectionDot = 'bg-primary size-1.5 rounded-full';
    $eyebrow = 'text-text-low text-[0.7rem] font-semibold tracking-[0.14em] uppercase';
@endphp

<x-portal::layouts.app>
    <main class="pb-28">
        <header class="relative isolate -mt-24 overflow-hidden pt-24">
            @if ($profile->coverUrl)
                <img
                    src="{{ $profile->coverUrl }}"
                    alt=""
                    class="absolute inset-0 -z-20 size-full scale-105 object-cover opacity-60"
                />
            @endif

            <div
                aria-hidden="true"
                class="absolute inset-0 -z-20 bg-[radial-gradient(70%_60%_at_18%_0%,rgb(120_43_241/0.55),transparent_72%),radial-gradient(55%_55%_at_88%_8%,rgb(152_40_189/0.35),transparent_72%)] mix-blend-screen"
            ></div>

            <div
                aria-hidden="true"
                class="from-elevation-surface/0 via-elevation-surface/55 to-elevation-surface absolute inset-0 -z-10 bg-gradient-to-b"
            ></div>

            <div class="mx-auto max-w-5xl px-4 pt-14 pb-8 sm:pt-20">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end">
                    @if ($profile->avatarUrl)
                        <img
                            src="{{ $profile->avatarUrl }}"
                            alt="Foto de {{ $profile->name }}"
                            class="bg-elevation-surface ring-elevation-surface size-28 shrink-0 rounded-full object-cover ring-4 sm:size-32"
                            style="box-shadow: 0 18px 48px -12px rgb(120 43 241 / 0.75)"
                        />
                    @else
                        <div
                            aria-hidden="true"
                            class="from-primary ring-elevation-surface flex size-28 shrink-0 items-center justify-center rounded-full bg-gradient-to-br to-fuchsia-500 text-4xl font-bold text-white ring-4 sm:size-32"
                            style="box-shadow: 0 18px 48px -12px rgb(120 43 241 / 0.75)"
                        >
                            {{ $profile->initials }}
                        </div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <h1 class="text-text-high text-3xl font-bold tracking-tight sm:text-4xl">{{ $profile->name }}</h1>

                        <p class="text-text-medium mt-1 text-sm">
                            {{ '@' . $profile->username }}

                            @if ($profile->nickname)
                                <span class="text-text-low">·</span>
                                {{ $profile->nickname }}
                            @endif
                        </p>

                        @if ($profile->headline)
                            <p class="text-text-high mt-3 max-w-xl leading-relaxed">{{ $profile->headline }}</p>
                        @endif
                    </div>

                    @if ($profile->availableForProposals)
                        <span
                            class="bg-helper-success/15 text-helper-success ring-helper-success/25 inline-flex shrink-0 items-center gap-2 self-start rounded-full px-3.5 py-2 text-xs font-semibold ring-1 sm:self-end"
                        >
                            <span class="bg-helper-success size-1.5 animate-pulse rounded-full"></span>
                            Disponível para propostas
                        </span>
                    @endif
                </div>

                @if ($profile->currentPosition || $profile->location || $profile->memberFor)
                    <div class="text-text-medium mt-7 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                        @if ($profile->currentPosition)
                            <span class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-m-briefcase" class="text-icon-medium size-4 shrink-0" />
                                {{ $profile->currentPosition }}@if ($profile->currentCompany)
                                    · {{ $profile->currentCompany }}
                                @endif
                            </span>
                        @endif

                        @if ($profile->location)
                            <span class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-m-map-pin" class="text-icon-medium size-4 shrink-0" />
                                {{ $profile->location }}
                            </span>
                        @endif

                        @if ($profile->memberFor)
                            <span class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-m-sparkles" class="text-icon-medium size-4 shrink-0" />
                                Membro há {{ $profile->memberFor }}
                            </span>
                        @endif
                    </div>
                @endif

                @if ($links !== [])
                    <nav class="mt-6" aria-labelledby="perfil-links">
                        <h2 id="perfil-links" class="sr-only">Links</h2>

                        <ul class="flex flex-wrap gap-2">
                            @foreach ($links as $link)
                                <li>
                                    @if ($link->url)
                                        <a
                                            href="{{ $link->url }}"
                                            target="_blank"
                                            rel="noopener noreferrer me"
                                            title="{{ $link->label }} — {{ $link->handle }}"
                                            class="border-outline-low bg-elevation-01dp/70 text-icon-medium hover:border-primary/50 hover:text-text-high flex size-10 items-center justify-center rounded-xl border backdrop-blur transition-colors"
                                        >
                                            <x-filament::icon :icon="$link->icon" class="size-[18px]" />
                                            <span class="sr-only">{{ $link->label }} — {{ $link->handle }}</span>
                                        </a>
                                    @else
                                        <span
                                            title="{{ $link->label }} — {{ $link->handle }}"
                                            class="border-outline-low bg-elevation-01dp/70 text-icon-medium flex size-10 items-center justify-center rounded-xl border backdrop-blur"
                                        >
                                            <x-filament::icon :icon="$link->icon" class="size-[18px]" />
                                            <span class="sr-only">{{ $link->label }} — {{ $link->handle }}</span>
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            </div>
        </header>

        <div class="mx-auto max-w-5xl px-4">
            @if ($facts !== [] || $workPreferences !== [])
                <div class="{{ $card }} divide-outline-low flex flex-col divide-y sm:flex-row sm:divide-x sm:divide-y-0 sm:p-0">
                    @foreach ($facts as $label => $value)
                        <div class="flex-1 py-4 first:pt-0 last:pb-0 sm:px-6 sm:py-5 sm:first:pt-5 sm:last:pb-5">
                            <p class="{{ $eyebrow }}">{{ $label }}</p>
                            <p class="text-text-high mt-1.5 text-lg font-semibold">{{ $value }}</p>
                        </div>
                    @endforeach

                    @if ($workPreferences !== [])
                        <div class="flex-1 py-4 first:pt-0 last:pb-0 sm:px-6 sm:py-5 sm:first:pt-5 sm:last:pb-5">
                            <p class="{{ $eyebrow }}">Preferências</p>

                            <ul class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($workPreferences as $preference)
                                    <li class="bg-primary/10 text-text-high rounded-lg px-2 py-1 text-xs font-medium">
                                        {{ $preference }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            @if ($hasBody || $hasAside)
                <div
                    @class([
                        'mt-6 grid items-start gap-6',
                        'lg:grid-cols-[1fr_20rem]' => $hasBody && $hasAside,
                        'mx-auto max-w-sm' => ! $hasBody && $hasAside,
                    ])
                >
                    @if ($hasBody)
                        <div class="min-w-0 space-y-6">
                            @if ($profile->about)
                                <section class="{{ $card }}">
                                    <h2 class="{{ $sectionHeading }}">
                                        <span class="{{ $sectionDot }}" style="box-shadow: 0 0 10px 2px rgb(120 43 241 / 0.9)"></span>
                                        Sobre
                                    </h2>

                                    <p class="text-text-medium mt-4 leading-relaxed whitespace-pre-line">{{ $profile->about }}</p>
                                </section>
                            @endif

                            @if ($profile->skills !== [])
                                <section class="{{ $card }}">
                                    <h2 class="{{ $sectionHeading }}">
                                        <span class="{{ $sectionDot }}" style="box-shadow: 0 0 10px 2px rgb(120 43 241 / 0.9)"></span>
                                        Skills
                                    </h2>

                                    <ul class="mt-4 flex flex-wrap gap-2">
                                        @foreach ($profile->skills as $skill)
                                            <li class="border-primary/25 bg-primary/10 flex items-baseline gap-2 rounded-full border px-3.5 py-1.5">
                                                <span class="text-text-high text-sm font-semibold">{{ $skill->name }}</span>
                                                <span class="text-text-medium text-xs">{{ $skill->proficiency }}</span>

                                                @if ($skill->yearsExperience)
                                                    <span class="text-text-low text-xs">
                                                        {{ $skill->yearsExperience }}{{ $skill->yearsExperience === 1 ? ' ano' : ' anos' }}
                                                    </span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </section>
                            @endif

                            @if ($profile->experiences !== [])
                                <section class="{{ $card }}">
                                    <h2 class="{{ $sectionHeading }}">
                                        <span class="{{ $sectionDot }}" style="box-shadow: 0 0 10px 2px rgb(120 43 241 / 0.9)"></span>
                                        Experiência profissional
                                    </h2>

                                    <ol class="mt-6 space-y-7">
                                        @foreach ($profile->experiences as $experience)
                                            <li class="border-outline-low relative border-l pb-1 pl-6 last:border-transparent last:pb-0">
                                                <span
                                                    class="bg-primary absolute top-1.5 -left-[5px] size-2.5 rounded-full"
                                                    style="box-shadow: 0 0 0 4px var(--elevation-01dp), 0 0 10px 2px rgb(120 43 241 / 0.6)"
                                                ></span>

                                                <div class="flex flex-wrap items-baseline gap-x-2">
                                                    <h3 class="text-text-high font-semibold">{{ $experience->position }}</h3>
                                                    <span class="text-text-medium text-sm">· {{ $experience->company }}</span>

                                                    @if ($experience->isCurrent)
                                                        <span class="bg-helper-success/15 text-helper-success rounded-full px-2 py-0.5 text-[0.7rem] font-semibold">
                                                            Atual
                                                        </span>
                                                    @endif
                                                </div>

                                                <p class="text-text-low mt-1 text-sm">
                                                    {{ $experience->period }}@if ($experience->duration)
                                                        · {{ $experience->duration }}
                                                    @endif
                                                </p>

                                                @if ($experience->description)
                                                    <p class="text-text-medium mt-2 text-sm leading-relaxed whitespace-pre-line">
                                                        {{ $experience->description }}
                                                    </p>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ol>
                                </section>
                            @endif

                            @if ($profile->projects !== [])
                                <section class="{{ $card }}">
                                    <h2 class="{{ $sectionHeading }}">
                                        <span class="{{ $sectionDot }}" style="box-shadow: 0 0 10px 2px rgb(120 43 241 / 0.9)"></span>
                                        Projetos
                                    </h2>

                                    <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                                        @foreach ($profile->projects as $project)
                                            <li class="border-outline-low hover:border-primary/40 bg-elevation-02dp/50 rounded-2xl border p-4 transition-colors">
                                                @if ($project->url)
                                                    <a
                                                        href="{{ $project->url }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="text-text-high hover:text-primary inline-flex items-center gap-1.5 font-semibold"
                                                    >
                                                        {{ $project->name }}
                                                        <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="text-icon-medium size-3.5 shrink-0" />
                                                    </a>
                                                @else
                                                    <h3 class="text-text-high font-semibold">{{ $project->name }}</h3>
                                                @endif

                                                @if ($project->description)
                                                    <p class="text-text-medium mt-1.5 text-sm leading-relaxed">{{ $project->description }}</p>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </section>
                            @endif
                        </div>
                    @endif

                    @if ($hasAside)
                        <aside class="min-w-0 lg:sticky lg:top-24" aria-labelledby="perfil-comunidade">
                            <div class="border-primary/20 rounded-3xl border bg-[linear-gradient(160deg,rgb(120_43_241/0.18),transparent_60%)] p-6 backdrop-blur-sm">
                                <h2 id="perfil-comunidade" class="sr-only">Comunidade</h2>

                                @if ($profile->level)
                                    <div class="flex items-baseline justify-between">
                                        <p class="{{ $eyebrow }}">
                                            <span class="sr-only">Nível {{ $profile->level }}</span>
                                            <span aria-hidden="true">Nível</span>
                                        </p>
                                        <p aria-hidden="true" class="text-text-high text-3xl leading-none font-bold">
                                            {{ $profile->level }}
                                        </p>
                                    </div>

                                    <div class="bg-elevation-surface/80 mt-3 h-2 w-full overflow-hidden rounded-full">
                                        <div
                                            class="from-primary h-full rounded-full bg-gradient-to-r to-fuchsia-400"
                                            style="width: {{ $profile->levelProgress }}%; box-shadow: 0 0 12px 1px rgb(120 43 241 / 0.8)"
                                        ></div>
                                    </div>

                                    <p class="text-text-low mt-2.5 text-xs">
                                        {{ number_format((float) $profile->experience, 0, ',', '.') }} XP
                                        @if ($profile->experienceToNextLevel)
                                            · {{ number_format((float) $profile->experienceToNextLevel, 0, ',', '.') }}
                                            para o próximo nível
                                        @endif
                                    </p>
                                @endif

                                @if ($profile->badges !== [])
                                    <h3 @class([$eyebrow, 'mt-7' => (bool) $profile->level])>
                                        Conquistas · {{ count($profile->badges) }}
                                    </h3>

                                    <ul class="mt-3 space-y-2.5">
                                        @foreach ($profile->badges as $badge)
                                            <li class="flex items-start gap-3">
                                                @if ($badge->imageUrl)
                                                    <img src="{{ $badge->imageUrl }}" alt="" class="size-9 shrink-0 rounded-xl object-contain" />
                                                @else
                                                    <span
                                                        aria-hidden="true"
                                                        class="bg-elevation-02dp text-text-medium flex size-9 shrink-0 items-center justify-center rounded-xl text-xs font-bold"
                                                    >
                                                        {{ mb_substr($badge->name, 0, 1) }}
                                                    </span>
                                                @endif

                                                <div class="min-w-0">
                                                    <h4 class="text-text-high text-sm font-semibold">{{ $badge->name }}</h4>
                                                    <p class="text-text-low mt-0.5 text-xs leading-snug">{{ $badge->description }}</p>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </aside>
                    @endif
                </div>
            @endif
        </div>
    </main>
</x-portal::layouts.app>
