@use (He4rt\Docs\Discovery\DTOs\AdrMetadata)
@use (He4rt\Docs\Discovery\DTOs\PlanMetadata)
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
                    <div class="flex flex-col">
                        <span class="text-primary">{{ config('app.name') }}</span>
                        <span class="text-xs">Docs</span>
                    </div>
                </x-slot>
            </flux:sidebar.brand>
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        @php ($boundaryShown = false)

        @foreach ($sidebar as $group)
            {{-- Visibility boundary: drawn once, right before the first noindex tier. --}}
            @if (!$group['indexable'] && !$boundaryShown)
                @php ($boundaryShown = true)
                <div class="not-prose mt-5 mb-2 px-3">
                    <div
                        class="flex items-center justify-between border-t border-dashed border-amber-300/70 pt-4 dark:border-amber-700/50"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 text-[0.66rem] font-bold tracking-wide text-amber-700 uppercase dark:text-amber-500"
                        >
                            <flux:icon.lock-closed class="size-3" />
                            Interno
                        </span>
                        <span
                            class="inline-flex items-center rounded-full border border-amber-200 bg-amber-100 px-2 py-px text-[0.6rem] font-bold text-amber-700 dark:border-amber-700/50 dark:bg-amber-900/30 dark:text-amber-400"
                        >
                            noindex
                        </span>
                    </div>
                    <p class="mt-1.5 text-[0.7rem] text-zinc-400 dark:text-zinc-500">Referência de engenharia — acessível, mas fora dos buscadores.</p>
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

    <flux:header :sticky="true" class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:spacer />
        <flux:button x-data="" x-on:click="$flux.dark = !$flux.dark" icon="moon" variant="subtle" />
    </flux:header>

    <flux:main class="flex justify-between p-0!">
        <article class="prose dark:prose-invert mx-auto max-w-[100ch]! p-8">
            @php ($meta = $document->metadata)
            @php ($moduleColor = $document->moduleName ? ModuleColor::for($document->moduleName) : null)

            <header class="not-prose mb-8 border-b border-zinc-100 pb-6 dark:border-zinc-800">
                <div class="mb-3 text-xs text-zinc-400 dark:text-zinc-500">
                    @if ($document->moduleName)
                        Engenharia / {{ Str::headline($document->moduleName) }}
                    @else
                        {{ $document->type->label() }}
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
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

            {!! $content !!}
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
