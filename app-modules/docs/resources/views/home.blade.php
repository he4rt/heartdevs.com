<x-docs::layout.guest :title="$title">
    <flux:sidebar
        :sticky="true"
        collapsible="mobile"
        class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900"
    >
        <flux:sidebar.header>
            <flux:sidebar.brand href="#" :logo="asset('images/logo.png')">
                <x-slot name="name">
                    <div class="flex flex-col">
                        <span class="text-primary">{{ config('app.name') }}</span>
                        <span class="text-xs">Docs</span>
                    </div>
                </x-slot>
            </flux:sidebar.brand>
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        @foreach ($sidebar as $navigationGroup)
            <flux:sidebar.nav>
                <flux:sidebar.group :expandable="true" :heading="$navigationGroup['title']">
                    @foreach ($navigationGroup['pages'] as $page)
                        <flux:sidebar.item :href="$page['url']">{{ $page['title'] }}</flux:sidebar.item>
                    @endforeach
                </flux:sidebar.group>
            </flux:sidebar.nav>
        @endforeach
    </flux:sidebar>
    <flux:header :sticky="true" class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:spacer />
        <flux:dropdown position="top" align="start">
            <flux:button x-data="" x-on:click="$flux.dark = ! $flux.dark">Toggle</flux:button>
        </flux:dropdown>
    </flux:header>
    <flux:main class="flex justify-between p-0!">
        <article class="prose dark:prose-invert mx-auto max-w-[100ch]! p-8">
            {!! $content !!}
        </article>
        {{-- Right Sidebar - Table of Contents --}}
        @if (count($toc) > 0)
            <aside
                x-data="{
                    active: '',
                    headings: [],
                    init() {
                        this.headings = JSON.parse(this.$el.dataset.headings)
                        this.active = this.headings.length > 0 ? this.headings[0] : ''

                        const observer = new IntersectionObserver(
                            (entries) => {
                                entries.forEach((entry) => {
                                    if (entry.isIntersecting) {
                                        this.active = entry.target.id
                                    }
                                })
                            },
                            { rootMargin: '0px 0px -80% 0px', threshold: 0.1 },
                        )

                        this.headings.forEach((id) => {
                            const el = document.getElementById(id)
                            if (el) observer.observe(el)
                        })
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
</x-docs::layout.guest>
