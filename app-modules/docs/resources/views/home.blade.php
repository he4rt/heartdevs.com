@use (He4rt\Docs\Discovery\DTOs\AdrMetadata)
@use (He4rt\Docs\Discovery\DTOs\PlanMetadata)
@use (He4rt\Docs\Discovery\Enums\DocumentTier)
@use (He4rt\Docs\Discovery\ModuleColor)
@use (Illuminate\Support\Str)

<x-docs::layout.guest :title="$title" :noindex="$noindex">
    <flux:sidebar
        :sticky="true"
        collapsible="mobile"
        class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900"
    >
        <flux:sidebar.header>
            <flux:sidebar.brand href="/docs" :logo="asset('images/logo.png')">
                <x-slot name="name">
                    <span class="font-semibold text-zinc-900 dark:text-white">
                        He4rt <span class="font-normal text-zinc-400 dark:text-zinc-500">/ Docs</span>
                    </span>
                </x-slot>
            </flux:sidebar.brand>
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        @php ($boundaryShown = false)

        @foreach ($sidebar as $group)
            {{-- Visibility boundary: drawn once, right before the first noindex tier. --}}
            @if (!$group['indexable'] && !$boundaryShown)
                @php ($boundaryShown = true)
                <div class="not-prose mt-6 mb-2 px-3">
                    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <span
                                class="inline-flex items-center gap-1.5 text-[0.66rem] font-bold tracking-wide text-zinc-500 uppercase dark:text-zinc-400"
                            >
                                <flux:icon.lock-closed class="size-3 text-amber-500" />
                                Interno
                            </span>
                            <span
                                class="inline-flex items-center rounded-full bg-amber-100 px-2 py-px text-[0.6rem] font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                            >
                                noindex
                            </span>
                        </div>
                        <p class="mt-1.5 text-[0.7rem] text-zinc-500 dark:text-zinc-400">Referência técnica — acessível, mas fora dos buscadores.</p>
                    </div>
                </div>
            @endif
            <flux:sidebar.nav>
                <flux:sidebar.group :expandable="true" :heading="$group['title']" :icon="$group['icon']">
                    @foreach ($group['pages'] as $page)
                        <flux:sidebar.item :href="$page['url']" :current="$page['url'] === $currentUrl">
                            {{ $page['title'] }}
                        </flux:sidebar.item>
                    @endforeach

                    @foreach ($group['subgroups'] as $subgroup)
                        @php ($dotColor = ModuleColor::for($subgroup['moduleName']))
                        <flux:sidebar.group :expandable="true" :heading="$subgroup['title']">
                            <x-slot:icon>
                                <span
                                    class="block size-2.5 rounded-full ring-3 ring-black/[0.03] dark:ring-white/[0.06]"
                                    style="background: {{ $dotColor }}"
                                ></span>
                            </x-slot:icon>

                            @foreach ($subgroup['pages'] as $page)
                                <flux:sidebar.item :href="$page['url']" :current="$page['url'] === $currentUrl">
                                    {{ $page['title'] }}
                                </flux:sidebar.item>
                            @endforeach
                        </flux:sidebar.group>
                    @endforeach
                </flux:sidebar.group>
            </flux:sidebar.nav>
        @endforeach
    </flux:sidebar>

    <flux:header
        :sticky="true"
        class="border-b border-zinc-200 bg-white/85 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85"
    >
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        {{-- Busca: placeholder visual; a busca funcional entra depois. --}}
        <button
            type="button"
            aria-label="Buscar na documentação (em breve)"
            class="flex w-full max-w-md items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-sm text-zinc-400 transition hover:border-zinc-300 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-500 dark:hover:bg-zinc-800"
        >
            <flux:icon.magnifying-glass class="size-4" />
            <span>Buscar na documentação...</span>
            <kbd
                class="ml-auto rounded border border-zinc-200 bg-white px-1.5 font-mono text-[0.7rem] dark:border-zinc-700 dark:bg-zinc-900"
                >⌘K</kbd
            >
        </button>

        <flux:spacer />

        <a
            href="https://github.com/he4rt/heartdevs.com"
            target="_blank"
            rel="noopener"
            aria-label="Repositório no GitHub"
            class="grid size-9 place-items-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
        >
            <svg viewBox="0 0 24 24" fill="currentColor" class="size-5" aria-hidden="true">
                <path
                    d="M12 2C6.48 2 2 6.58 2 12.25c0 4.53 2.87 8.37 6.84 9.73.5.1.68-.22.68-.49l-.01-1.7c-2.78.62-3.37-1.37-3.37-1.37-.45-1.18-1.11-1.5-1.11-1.5-.91-.64.07-.62.07-.62 1 .07 1.53 1.06 1.53 1.06.9 1.57 2.36 1.12 2.94.86.09-.67.35-1.12.63-1.38-2.22-.26-4.55-1.14-4.55-5.06 0-1.12.39-2.03 1.03-2.75-.1-.26-.45-1.3.1-2.72 0 0 .84-.28 2.75 1.05a9.3 9.3 0 0 1 5 0c1.91-1.33 2.75-1.05 2.75-1.05.55 1.42.2 2.46.1 2.72.64.72 1.03 1.63 1.03 2.75 0 3.93-2.34 4.79-4.57 5.05.36.32.68.94.68 1.9l-.01 2.82c0 .27.18.6.69.49A10.26 10.26 0 0 0 22 12.25C22 6.58 17.52 2 12 2z"
                />
            </svg>
        </a>

        <a
            href="https://discord.gg/he4rt"
            target="_blank"
            rel="noopener"
            class="bg-primary-600 hover:bg-primary-700 inline-flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-sm font-semibold text-white transition"
        >
            Discord
        </a>

        <flux:button
            x-data
            x-on:click="$flux.dark = !$flux.dark"
            icon="moon"
            variant="subtle"
            size="sm"
            aria-label="Alternar tema claro/escuro"
        />
    </flux:header>

    <flux:main id="conteudo" tabindex="-1" class="flex justify-between p-0!">
        <article class="prose dark:prose-invert mx-auto max-w-[72ch]! p-8">
            @php ($meta = $document->metadata)
            @php ($moduleColor = $document->moduleName ? ModuleColor::for($document->moduleName) : null)
            @php ($tier = DocumentTier::for($document))

            <header class="not-prose mb-8 border-b border-zinc-100 pb-6 dark:border-zinc-800">
                <nav
                    aria-label="Trilha de navegação"
                    class="mb-3 flex flex-wrap items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400"
                >
                    <span>{{ $tier->label() }}</span>
                    @if ($document->moduleName)
                        <flux:icon.chevron-right class="size-3" />
                        <span>{{ Str::headline($document->moduleName) }}</span>
                    @endif
                    <flux:icon.chevron-right class="size-3" />
                    <span class="text-zinc-600 dark:text-zinc-300" aria-current="page">{{ $document->title }}</span>
                </nav>

                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        style="background: color-mix(in srgb, #782bf1 12%, transparent); color: #782bf1"
                    >
                        <span class="size-1.5 rounded-full" style="background: #782bf1"></span>
                        {{ $tier->label() }}
                    </span>

                    @if ($document->readingMinutes > 0)
                        <span
                            class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"
                        >
                            <flux:icon.clock class="size-3.5" />
                            Leitura · {{ $document->readingMinutes }} min
                        </span>
                    @endif

                    @if ($document->moduleName)
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            style="background: color-mix(in srgb, {{ $moduleColor }} 14%, transparent); color: {{ $moduleColor }}"
                        >
                            <span class="size-1.5 rounded-full" style="background: {{ $moduleColor }}"></span>
                            {{ Str::headline($document->moduleName) }}
                        </span>
                    @endif

                    @if ($meta instanceof AdrMetadata)
                        <flux:badge :color="$meta->status->color()" size="sm">{{ $meta->status->label() }}</flux:badge>
                    @elseif ($meta instanceof PlanMetadata)
                        <flux:badge :color="$meta->status->color()" size="sm">
                            {{ $meta->status->label() }} · {{ $meta->progress() }}%
                        </flux:badge>
                    @endif

                    @if ($document->date)
                        <span
                            class="text-sm text-zinc-500 dark:text-zinc-400"
                            >{{ $document->date->format('d/m/Y') }}</span
                        >
                    @endif

                    @if ($noindex)
                        <span
                            class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:border-amber-700/50 dark:bg-amber-900/30 dark:text-amber-400"
                            title="Material de engenharia, não indexado por buscadores"
                        >
                            <flux:icon.eye-slash class="size-3.5" />
                            documento interno · noindex
                        </span>
                    @endif
                </div>

                <h1 class="mt-3 text-3xl font-bold text-zinc-900 dark:text-white">{{ $document->title }}</h1>

                @if ($meta instanceof AdrMetadata && count($meta->deciders) > 0)
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Deciders:</span>
                        @foreach ($meta->deciders as $decider)
                            <a
                                href="https://github.com/{{ $decider }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 py-0.5 pr-3 pl-0.5 text-sm text-zinc-700 transition hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                            >
                                <img
                                    src="https://github.com/{{ $decider }}.png"
                                    alt="{{ $decider }}"
                                    class="size-6 rounded-full"
                                    loading="lazy"
                                />
                                <span>{{ $decider }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($meta instanceof AdrMetadata && count($meta->relations) > 0)
                    <div class="mt-3 flex flex-col gap-1 text-sm text-zinc-600 dark:text-zinc-400">
                        @foreach ($meta->relations as $relation)
                            <span
                                ><span class="font-semibold">{{ $relation['label'] }}:</span>
                                {{ $relation['target'] }}</span
                            >
                        @endforeach
                    </div>
                @endif
            </header>

            @if ($document->isDatedArtifact())
                <div
                    role="note"
                    class="not-prose mb-6 flex gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-700/50 dark:bg-amber-900/20 dark:text-amber-300"
                >
                    <flux:icon.clock class="size-5 shrink-0" />
                    <span>
                        Artefato de planejamento{{ $document->date ? ' de ' . $document->date->format('d/m/Y') : '' }} —
                        pode não refletir o estado atual do código.
                    </span>
                </div>
            @endif

            {{-- O título já é exibido no header; remove o primeiro H1 do corpo para não duplicar (e manter um único h1 na página). --}}
            {!!
                preg_replace(
                    '/<h1\b[^>]*>.*?<\/h1>/is',
                    '',
                    $content,
                    1,
                )
            !!}
        </article>

        @if (count($toc) > 0)
            <aside
                x-data="{
                    active: '',
                    headings: [],
                    init() {
                        this.headings = JSON.parse(this.$el.dataset.headings);
                        this.active = this.headings.length > 0 ? this.headings[0] : '';

                        const observer = new IntersectionObserver(
                            (entries) => {
                                entries.forEach((entry) => {
                                    if (entry.isIntersecting) {
                                        this.active = entry.target.id;
                                    }
                                });
                            },
                            { rootMargin: '0px 0px -80% 0px', threshold: 0.1 },
                        );

                        this.headings.forEach((id) => {
                            const el = document.getElementById(id);
                            if (el) observer.observe(el);
                        });
                    },
                }"
                data-headings="{{ json_encode(collect($toc)->pluck('id')) }}"
                class="hidden w-64 shrink-0 border-l border-zinc-200 bg-white xl:block dark:border-zinc-800 dark:bg-zinc-900"
            >
                <nav class="sticky top-0 max-h-screen overflow-y-auto p-6">
                    <h3 class="mb-4 text-xs font-bold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">
                        Nesta página
                    </h3>
                    <ul class="space-y-3 border-l border-zinc-100 dark:border-zinc-800">
                        @foreach ($toc as $heading)
                            <li class="{{ $heading['level'] === 3 ? 'ml-4' : '' }}">
                                <a
                                    href="#{{ $heading['id'] }}"
                                    :class="{
                                                'border-primary-500 text-primary-600 dark:text-primary-400 font-medium': active === '{{ $heading['id'] }}',
                                                'border-transparent text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200': active !== '{{ $heading['id'] }}'
                                            }"
                                    class="-ml-px block border-l-2 pl-4 text-sm transition-all duration-200"
                                    @click.prevent="document.getElementById('{{ $heading['id'] }}').scrollIntoView({ behavior: 'smooth' }); active = '{{ $heading['id'] }}'"
                                >
                                    {{ $heading['text'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </aside>
        @endif
    </flux:main>

    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.esm.min.mjs';

        mermaid.initialize({ startOnLoad: false, securityLevel: 'strict' });

        const blocks = document.querySelectorAll('pre > code.language-mermaid');

        if (blocks.length > 0) {
            mermaid.run({ nodes: blocks });
        }
    </script>
</x-docs::layout.guest>
