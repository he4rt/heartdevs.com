<x-portal::layouts.app title="{{ $profile->nickname ?? $user->name }} — He4rt Devs">
    @push ('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
        @vite (['app-modules/profile/resources/css/profile.css'])
    @endpush

    <div class="profile-page">
        <div class="mx-auto max-w-6xl px-4 py-10">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-[300px_1fr]">
                <!-- Sidebar -->
                <aside class="flex flex-col gap-5 lg:sticky lg:top-10 lg:self-start">
                    <div class="flex justify-center lg:justify-start">
                        <div class="avatar-wrap">
                            <div class="avatar-ring-glow"></div>
                            <div class="avatar-core">
                                @if ($user->getFirstMediaUrl('avatar'))
                                    <img
                                        src="{{ $user->getFirstMediaUrl('avatar') }}"
                                        alt="{{ $user->name }}"
                                        class="size-full rounded-full object-cover"
                                    />
                                @else
                                    <img
                                        src="https://i.pravatar.cc/300?u={{ $user->username }}"
                                        alt="{{ $user->name }}"
                                        class="size-full rounded-full object-cover"
                                    />
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="text-center lg:text-left">
                        <h1 class="font-display inline-flex items-center gap-2 text-2xl font-bold">
                            {{ $user->name }}
                            @if ($user->character && $user->character->badges->contains('redeem_code', 'H4_VERIFIED'))
                                <span
                                    class="bg-e2 border-ol inline-flex items-center gap-1 rounded-full border px-2 py-0.5"
                                    title="Verificado He4rt"
                                >
                                    <span class="text-primary text-[9px] leading-none font-bold">H4</span>
                                    <svg viewBox="0 0 600 513" xmlns="http://www.w3.org/2000/svg" class="size-[9px] shrink-0" fill="#782bf1">
                                        <path d="M445.237 0.00033551C424.91 -0.0347431 404.777 3.89304 385.996 11.5576C367.216 19.2221 350.159 30.4719 335.808 44.6594L153.391 224.398L116.915 188.45C111.983 183.761 108.048 178.15 105.341 171.946C102.633 165.741 101.207 159.067 101.145 152.314C101.084 145.56 102.388 138.862 104.983 132.611C107.577 126.359 111.409 120.68 116.255 115.904C121.101 111.128 126.864 107.352 133.207 104.795C139.55 102.239 146.347 100.953 153.2 101.014C160.052 101.074 166.825 102.48 173.12 105.148C179.416 107.816 185.109 111.694 189.867 116.555L262.856 44.6594C233.71 16.6424 194.537 1.07109 153.824 1.31914C113.11 1.56719 74.1349 17.6146 45.3431 45.9846C16.5513 74.3546 0.261527 112.762 0.0031216 152.886C-0.255283 193.01 15.5385 231.618 43.9626 260.346L153.391 368.189L511.948 14.8274C491.12 5.01981 468.32 -0.0474973 445.237 0.00033551Z" />
                                        <path d="M584.9 86.7579L445.237 224.433L408.76 188.45L335.808 260.345L372.284 296.293L226.379 440.084L299.332 512.015L554.665 260.381C577.296 238.07 592.355 209.395 597.769 178.303C603.183 147.21 598.687 115.228 584.9 86.7579Z" />
                                    </svg>
                                </span>
                            @endif
                        </h1>
                        <div class="glow-rule mx-auto mt-2 lg:mx-0"></div>
                    </div>

                    @if ($profile->headline || $profile->seniority_level)
                        <div class="flex items-center justify-center gap-2 lg:justify-start">
                            <p class="text-tm text-sm">{{
                                $profile->headline ??
                                    __('profile::enums.seniority_level.' . $profile->seniority_level->value, [], 'pt_BR')
                            }}</p>
                            @if ($profile->seniority_level)
                                <span
                                    class="bg-primary/10 text-primary border-primary/20 inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
                                >
                                    {{
                                        __(
                                            'profile::enums.seniority_level.' . $profile->seniority_level->value,
                                            [],
                                            'pt_BR',
                                        )
                                    }}
                                </span>
                            @endif
                        </div>
                    @endif

                    @if ($user->address)
                        <div class="text-tm flex items-center justify-center gap-1.5 text-sm lg:justify-start">
                            <i class="fa-solid fa-location-dot text-tl text-xs"></i>
                            {{ $user->address->city }}{{
                                $user->address->state
                                    ? ', ' . $user->address->state
                                    : ''
                            }}
                        </div>
                    @endif

                    @if ($profile->work_types && count($profile->work_types) > 0)
                        <div class="flex flex-wrap items-center justify-center gap-1.5 lg:justify-start">
                            @foreach ($profile->work_types as $workType)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-medium"
                                    style="
                                        background: var(--status-green-bg);
                                        border: 1px solid var(--status-green-border);
                                        color: var(--status-green-text);
                                    "
                                >
                                    <i class="fa-solid fa-check text-[10px]"></i>
                                    {{
                                        __(
                                            'profile::enums.work_type.' . $workType,
                                            [],
                                            'pt_BR',
                                        )
                                    }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if ($profile->social_links && count($profile->social_links) > 0)
                        <div class="flex items-center justify-center gap-2 lg:justify-start">
                            @foreach ($profile->social_links as $platform => $url)
                                @if ($url)
                                    <a
                                        href="{{ $url }}"
                                        target="_blank"
                                        class="card text-tm hover:bg-primary/20 flex size-9 items-center justify-center rounded-lg transition-colors hover:text-white"
                                    >
                                        <i class="{{ $socialIcons[$platform] ?? 'fas fa-link' }} text-base"></i>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if ($resumeUrl)
                        <a
                            href="{{ $resumeUrl }}"
                            target="_blank"
                            class="card text-tm hover:bg-primary/20 flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-colors hover:text-white"
                        >
                            <i class="fa-solid fa-download text-sm"></i>
                            Download do currículo
                        </a>
                    @endif

                    @if ($user->character)
                        <div class="card p-5">
                            <div class="flex items-center gap-3">
                                <div class="bg-primary/10 flex size-10 items-center justify-center rounded-full">
                                    <svg class="text-primary size-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                </div>
                                <div>
                                    <p class="text-tl text-xs">Nível da Comunidade</p>
                                    <p class="font-display text-lg font-bold">{{ $user->character->level }}</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                @php
                                    $xpBarLevel = $user->character->level;
                                    $xpBarNext = \He4rt\Gamification\Character\Models\Character::LEVEL_THRESHOLDS[$xpBarLevel + 1] ?? 1;
                                    $xpBarPercent = $xpBarNext > 0 ? round(($user->character->experience / $xpBarNext) * 100, 1) : 100;
                                @endphp
                                <div class="xp-track h-2 w-full overflow-hidden rounded-full">
                                    <div
                                        class="xp-bar h-full rounded-full"
                                        style="width: {{ max($xpBarPercent, 1) }}%;"
                                    ></div>
                                </div>
                                <p class="text-tm mt-2 text-xs">{{ number_format($user->character->experience) }} XP · {{ number_format($user->character->experience_progress) }} para o próximo nível</p>
                            </div>
                        </div>
                    @endif
                </aside>

                <!-- Main -->
                <main class="flex flex-col gap-6">
                    @if ($profile->about)
                        <div class="card p-6">
                            <h2 class="font-display mb-4 text-lg font-semibold">Sobre</h2>
                            <p class="text-tm text-base leading-relaxed">{{ $profile->about }}</p>
                        </div>
                    @endif

                    @php
                        $allSkills = collect($profile->skillsByCategory());
                        $hasLanguages = $profile->languages && count($profile->languages) > 0;
                    @endphp
                    @if ($allSkills->isNotEmpty() || $hasLanguages)
                        <div class="card p-6">
                            <h2 class="font-display mb-4 text-base font-semibold">Stack & Skills</h2>
                            <div class="flex flex-col gap-4">
                                @php
                                    $chipPurple = 'skill-chip inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium';
                                    $chipPurpleStyle =
                                        'background:var(--chip-purple-bg);border:1px solid var(--chip-purple-border);color:var(--chip-purple-text)';
                                    $chipCyan = 'skill-chip inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium';
                                    $chipCyanStyle = 'background:var(--chip-cyan-bg);border:1px solid var(--chip-cyan-border);color:var(--chip-cyan-text)';
                                    $chipGray = 'skill-chip inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium';
                                    $chipGrayStyle = 'background:var(--chip-gray-bg);border:1px solid var(--chip-gray-border);color:var(--chip-gray-text)';
                                @endphp

                                @php
                                    $langFw = $allSkills->where('category', 'languages_frameworks');
                                @endphp
                                @if ($langFw->isNotEmpty())
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($langFw as $skill)
                                            <span class="{{ $chipPurple }}" style="{{ $chipPurpleStyle }}">
                                                @if ($skill['icon'])
                                                    <i class="{{ $skill['icon'] }} text-[11px]"></i>
                                                @endif
                                                {{ $skill['name'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @php
                                    $infraDb = $allSkills->where('category', 'infra_databases');
                                @endphp
                                @if ($infraDb->isNotEmpty())
                                    <div class="skill-divider"></div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($infraDb as $skill)
                                            <span class="{{ $chipCyan }}" style="{{ $chipCyanStyle }}">
                                                @if ($skill['icon'])
                                                    <i class="{{ $skill['icon'] }} text-[11px]"></i>
                                                @endif
                                                {{ $skill['name'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @php
                                    $softTools = $allSkills->where('category', 'softskills_tools');
                                @endphp
                                @if ($softTools->isNotEmpty())
                                    <div class="skill-divider"></div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($softTools as $skill)
                                            <span class="{{ $chipGray }}" style="{{ $chipGrayStyle }}">
                                                @if ($skill['icon'])
                                                    <i class="{{ $skill['icon'] }} text-[11px]"></i>
                                                @endif
                                                {{ $skill['name'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($profile->languages && count($profile->languages) > 0)
                                    <div class="skill-divider"></div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($profile->languages as $lang)
                                            <span class="{{ $chipGray }}" style="{{ $chipGrayStyle }}">
                                                <i class="fa-solid fa-globe text-[11px]"></i>
                                                {{ $lang['name'] }} · {{ $lang['level'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Projetos -->
                    @if ($projects->isNotEmpty())
                        <div class="card p-6" x-data="{ expanded: false }">
                            <h2 class="font-display mb-4 text-base font-semibold">Projetos</h2>
                            @foreach ($projects->take(3) as $project)
                                <div class="rounded-lg border border-ol bg-e2 p-5 {{ !$loop->first ? 'mt-3' : '' }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <h3 class="text-sm font-semibold">{{ $project->name }}</h3>
                                            <p class="text-tm mt-1 text-xs leading-relaxed">{{ $project->description }}</p>
                                        </div>
                                        @if ($project->url)
                                            <a
                                                href="{{ $project->url }}"
                                                target="_blank"
                                                class="text-tm hover:text-primary shrink-0 transition-colors"
                                            >
                                                <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                            </a>
                                        @endif
                                    </div>
                                    @if ($project->tags)
                                        <div class="mt-3 flex flex-wrap gap-1.5">
                                            @foreach ($project->tags as $tag)
                                                <span
                                                    class="bg-e2 border-ol text-tm rounded-md border px-1.5 py-0.5 text-[10px] font-medium"
                                                    >{{ $tag }}</span
                                                >
                                            @endforeach
                                        </div>
                                    @endif
                                    @if ($project->stars || $project->forks)
                                        <div
                                            class="border-ol text-tl mt-3 flex items-center gap-4 border-t pt-3 text-[11px]"
                                        >
                                            @if ($project->stars)
                                                <span class="inline-flex items-center gap-1"
                                                    ><i class="fa-solid fa-star text-[10px]"></i>
                                                    {{ $project->stars }}</span
                                                >
                                            @endif
                                            @if ($project->forks)
                                                <span class="inline-flex items-center gap-1"
                                                    ><i class="fa-solid fa-code-fork text-[10px]"></i>
                                                    {{ $project->forks }}</span
                                                >
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                            @if ($projects->count() > 3)
                                <div x-show="expanded" x-transition>
                                    @foreach ($projects->skip(3) as $project)
                                        <div class="border-ol bg-e2 mt-3 rounded-lg border p-5">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0 flex-1">
                                                    <h3 class="text-sm font-semibold">{{ $project->name }}</h3>
                                                    <p class="text-tm mt-1 text-xs leading-relaxed">{{ $project->description }}</p>
                                                </div>
                                                @if ($project->url)
                                                    <a
                                                        href="{{ $project->url }}"
                                                        target="_blank"
                                                        class="text-tm hover:text-primary shrink-0 transition-colors"
                                                    >
                                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            @if ($project->tags)
                                                <div class="mt-3 flex flex-wrap gap-1.5">
                                                    @foreach ($project->tags as $tag)
                                                        <span
                                                            class="bg-e2 border-ol text-tm rounded-md border px-1.5 py-0.5 text-[10px] font-medium"
                                                            >{{ $tag }}</span
                                                        >
                                                    @endforeach
                                                </div>
                                            @endif
                                            @if ($project->stars || $project->forks)
                                                <div
                                                    class="border-ol text-tl mt-3 flex items-center gap-4 border-t pt-3 text-[11px]"
                                                >
                                                    @if ($project->stars)
                                                        <span class="inline-flex items-center gap-1"
                                                            ><i class="fa-solid fa-star text-[10px]"></i>
                                                            {{ $project->stars }}</span
                                                        >
                                                    @endif
                                                    @if ($project->forks)
                                                        <span class="inline-flex items-center gap-1"
                                                            ><i class="fa-solid fa-code-fork text-[10px]"></i>
                                                            {{ $project->forks }}</span
                                                        >
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-4 text-center">
                                    <button
                                        @click="expanded = !expanded"
                                        class="text-primary hover:text-primary/80 cursor-pointer text-xs font-medium transition-colors"
                                    >
                                        <span x-text="expanded ? 'Ver menos ↑' : 'Ver todos os projetos →'"></span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- PRs -->
                    @if ($pullRequests->isNotEmpty())
                        <div class="card p-6" x-data="{ expanded: false }">
                            <h2 class="font-display mb-4 text-base font-semibold">PRs na Comunidade</h2>
                            <div class="flex flex-col gap-3">
                                @foreach ($pullRequests->take(3) as $pr)
                                    <div class="border-ol bg-e2 rounded-lg border p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium">{{ $pr->title }}</p>
                                                <p class="text-tm mt-0.5 text-xs">{{ $pr->repo }}</p>
                                            </div>
                                            <span
                                                class="shrink-0 rounded-full {{ $pr->status_class }} px-2 py-0.5 text-[10px] font-medium"
                                            >
                                                {{ $pr->status_label }}
                                            </span>
                                        </div>
                                        <div class="text-tl mt-2 flex items-center gap-3 text-[11px]">
                                            <span class="inline-flex items-center gap-1">
                                                @if ($pr->status === 'merged')
                                                    <i class="fa-solid fa-code-merge text-[10px]"></i>
                                                @else
                                                    <i class="fa-solid fa-code-pull-request text-[10px]"></i>
                                                @endif
                                                #{{ $pr->number }}
                                            </span>
                                            @if ($pr->pr_created_at)
                                                <span>{{ $pr->pr_created_at->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                                @if ($pullRequests->count() > 3)
                                    <div x-show="expanded" x-transition>
                                        @foreach ($pullRequests->skip(3) as $pr)
                                            <div
                                                class="border-ol bg-e2 rounded-lg border p-4 {{ !$loop->first ? 'mt-3' : '' }}"
                                            >
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-sm font-medium">{{ $pr->title }}</p>
                                                        <p class="text-tm mt-0.5 text-xs">{{ $pr->repo }}</p>
                                                    </div>
                                                    <span
                                                        class="shrink-0 rounded-full {{ $pr->status_class }} px-2 py-0.5 text-[10px] font-medium"
                                                    >
                                                        {{ $pr->status_label }}
                                                    </span>
                                                </div>
                                                <div class="text-tl mt-2 flex items-center gap-3 text-[11px]">
                                                    <span class="inline-flex items-center gap-1">
                                                        @if ($pr->status === 'merged')
                                                            <i class="fa-solid fa-code-merge text-[10px]"></i>
                                                        @else
                                                            <i class="fa-solid fa-code-pull-request text-[10px]"></i>
                                                        @endif
                                                        #{{ $pr->number }}
                                                    </span>
                                                    @if ($pr->pr_created_at)
                                                        <span>{{ $pr->pr_created_at->diffForHumans() }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @if ($pullRequests->count() > 3)
                                <div class="mt-4 text-center">
                                    <button
                                        @click="expanded = !expanded"
                                        class="text-primary hover:text-primary/80 cursor-pointer text-xs font-medium transition-colors"
                                    >
                                        <span x-text="expanded ? 'Ver menos ↑' : 'Ver todos os PRs →'"></span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Badges -->
                    @php
                        $allBadges = $user->character?->badges ?? collect();
                        $h4Badge = $allBadges->firstWhere('redeem_code', 'H4_VERIFIED');
                        $otherBadges = $allBadges->reject(fn($b) => $b->redeem_code === 'H4_VERIFIED');
                    @endphp
                    @if ($h4Badge || $otherBadges->isNotEmpty())
                        <div class="card p-6" x-data="{ expanded: false }">
                            <h2 class="font-display mb-3 text-base font-semibold">Badges He4rt</h2>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @if ($h4Badge)
                                    <div class="border-ol bg-e2 flex items-center gap-3 rounded-lg border p-4">
                                        <div
                                            class="bg-primary/10 flex size-10 shrink-0 items-center justify-center gap-0.5 rounded-lg"
                                        >
                                            <span class="text-primary text-[9px] leading-none font-bold">H4</span>
                                            <svg viewBox="0 0 600 513" xmlns="http://www.w3.org/2000/svg" class="size-[9px] shrink-0" fill="#782bf1">
                                                <path d="M445.237 0.00033551C424.91 -0.0347431 404.777 3.89304 385.996 11.5576C367.216 19.2221 350.159 30.4719 335.808 44.6594L153.391 224.398L116.915 188.45C111.983 183.761 108.048 178.15 105.341 171.946C102.633 165.741 101.207 159.067 101.145 152.314C101.084 145.56 102.388 138.862 104.983 132.611C107.577 126.359 111.409 120.68 116.255 115.904C121.101 111.128 126.864 107.352 133.207 104.795C139.55 102.239 146.347 100.953 153.2 101.014C160.052 101.074 166.825 102.48 173.12 105.148C179.416 107.816 185.109 111.694 189.867 116.555L262.856 44.6594C233.71 16.6424 194.537 1.07109 153.824 1.31914C113.11 1.56719 74.1349 17.6146 45.3431 45.9846C16.5513 74.3546 0.261527 112.762 0.0031216 152.886C-0.255283 193.01 15.5385 231.618 43.9626 260.346L153.391 368.189L511.948 14.8274C491.12 5.01981 468.32 -0.0474973 445.237 0.00033551Z" />
                                                <path d="M584.9 86.7579L445.237 224.433L408.76 188.45L335.808 260.345L372.284 296.293L226.379 440.084L299.332 512.015L554.665 260.381C577.296 238.07 592.355 209.395 597.769 178.303C603.183 147.21 598.687 115.228 584.9 86.7579Z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-sm font-medium">Perfil Certificado pela Comunidade</h4>
                                            <p class="text-tm mt-0.5 text-xs">Certificação oficial da He4rt Devs</p>
                                        </div>
                                    </div>
                                @endif
                                @foreach ($otherBadges->take(4) as $badge)
                                    <div class="border-ol bg-e2 flex items-center gap-3 rounded-lg border p-4">
                                        <div
                                            class="bg-primary/10 flex size-10 shrink-0 items-center justify-center rounded-lg"
                                        >
                                            @if ($badge->getFirstMediaUrl('badge'))
                                                <img
                                                    src="{{ $badge->getFirstMediaUrl('badge') }}"
                                                    alt="{{ $badge->name }}"
                                                    class="size-5 object-contain"
                                                />
                                            @else
                                                <svg class="text-primary size-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-sm font-medium">{{ $badge->name }}</h4>
                                            @if ($badge->description)
                                                <p class="text-tm mt-0.5 text-xs">{{ $badge->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                                @if ($otherBadges->count() > 4)
                                    @foreach ($otherBadges->skip(4) as $badge)
                                        <div
                                            x-show="expanded"
                                            x-transition
                                            class="border-ol bg-e2 flex items-center gap-3 rounded-lg border p-4"
                                        >
                                            <div
                                                class="bg-primary/10 flex size-10 shrink-0 items-center justify-center rounded-lg"
                                            >
                                                @if ($badge->getFirstMediaUrl('badge'))
                                                    <img
                                                        src="{{ $badge->getFirstMediaUrl('badge') }}"
                                                        alt="{{ $badge->name }}"
                                                        class="size-5 object-contain"
                                                    />
                                                @else
                                                    <svg class="text-primary size-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <h4 class="text-sm font-medium">{{ $badge->name }}</h4>
                                                @if ($badge->description)
                                                    <p class="text-tm mt-0.5 text-xs">{{ $badge->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                                @if ($otherBadges->count() > 4)
                                    <div class="col-span-full text-center">
                                        <button
                                            @click="expanded = !expanded"
                                            class="text-primary hover:text-primary/80 cursor-pointer text-xs font-medium transition-colors"
                                        >
                                            <span x-text="expanded ? 'Ver menos ↑' : 'Ver todas as badges →'"></span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </main>
            </div>
        </div>
    </div>
</x-portal::layouts.app>
