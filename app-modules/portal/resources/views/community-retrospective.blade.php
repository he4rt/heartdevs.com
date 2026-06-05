<div class="min-h-screen bg-zinc-950 text-zinc-100">
    <div class="mx-auto max-w-5xl px-6 py-12">
        <header class="mb-10">
            <h1 class="text-3xl font-bold tracking-tight" style="color: #a78bfa">Quem fez a He4rt bater</h1>
            <p class="mt-2 text-zinc-400">Participação da comunidade nos repositórios públicos.</p>

            <form class="mt-6 flex flex-wrap items-end gap-3">
                <label class="flex flex-col text-sm text-zinc-400">
                    De
                    <input
                        type="date"
                        wire:model.live="since"
                        value="{{ $sinceValue }}"
                        class="mt-1 rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-zinc-100"
                    />
                </label>
                <label class="flex flex-col text-sm text-zinc-400">
                    Até
                    <input
                        type="date"
                        wire:model.live="until"
                        value="{{ $untilValue }}"
                        class="mt-1 rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-zinc-100"
                    />
                </label>
                <span class="pb-2 text-xs text-zinc-500"
                    >{{ $data['period']['since'] }} → {{ $data['period']['until'] }}</span
                >
            </form>
        </header>

        @php
            $cards = [
                ['label' => 'Pessoas', 'value' => $data['meta']['people'], 'hint' => null],
                [
                    'label' => 'PRs',
                    'value' => $data['meta']['prs'],
                    'hint' => $data['meta']['prs_merged'] . ' merged · ' . $data['meta']['prs_unmerged'] . ' fechados',
                ],
                ['label' => 'Reviews', 'value' => $data['meta']['reviews'], 'hint' => null],
                ['label' => 'Issues', 'value' => $data['meta']['issues'], 'hint' => null],
                ['label' => 'Comentários', 'value' => $data['meta']['comments'], 'hint' => null],
                ['label' => 'Commits', 'value' => $data['meta']['commits'], 'hint' => null],
            ];
        @endphp
        <section class="mb-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($cards as $card)
                <div class="rounded-xl border border-zinc-800 bg-zinc-900/60 p-4">
                    <div class="text-2xl font-bold" style="color: #a78bfa">{{ $card['value'] }}</div>
                    <div class="text-xs tracking-wide text-zinc-500 uppercase">{{ $card['label'] }}</div>
                    @if ($card['hint'])
                        <div class="mt-1 text-[10px] text-zinc-600">{{ $card['hint'] }}</div>
                    @endif
                </div>
            @endforeach
        </section>

        <section class="space-y-3">
            @forelse ($data['people'] as $person)
                <a
                    href="{{ $person['url'] }}"
                    target="_blank"
                    rel="noopener"
                    class="flex items-center gap-4 rounded-xl border border-zinc-800 bg-zinc-900/60 p-4 transition hover:border-violet-500/60"
                >
                    <img
                        src="{{ $person['avatar'] }}"
                        alt="{{ $person['login'] }}"
                        class="size-12 rounded-full ring-2 ring-violet-500/40"
                        loading="lazy"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold">{{ '@' . $person['login'] }}</div>
                        <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-zinc-400">
                            <span>
                                {{ $person['prs'] }} PRs
                                @if ($person['prs_unmerged'] > 0)
                                    <span class="text-zinc-600" title="fechados sem merge"
                                        >· {{ $person['prs_unmerged'] }} não mergeado{{ $person['prs_unmerged'] > 1 ? 's' : '' }}</span
                                    >
                                @endif
                            </span>
                            <span>{{ $person['reviews'] }} reviews</span>
                            <span>{{ $person['issues'] }} issues</span>
                            <span>{{ $person['comments'] }} comentários</span>
                            <span>{{ $person['commits'] }} commits</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-bold" style="color: #a78bfa">{{ $person['total'] }}</div>
                        <div class="text-[10px] tracking-wide text-zinc-500 uppercase">interações</div>
                    </div>
                </a>
            @empty
                <p class="rounded-xl border border-dashed border-zinc-800 p-8 text-center text-zinc-500">Ninguém bateu a He4rt nessa janela.</p>
            @endforelse
        </section>
    </div>
</div>
