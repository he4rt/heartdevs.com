@props([
    'authors',
])

@php
    // Posto de cada pessoa quando a coluna é ordenada por alcance. A reordenação
    // acontece por `order` no flex, o que preserva a renderização no servidor.
    $byReactions = collect($authors)->sortByDesc(fn ($author): int => $author->reactions)->values();
    $reactionRank = $byReactions->mapWithKeys(fn ($author, int $rank): array => [$author->username => $rank])->all();
@endphp

<aside class="flex flex-col gap-3" aria-labelledby="articles-authors-heading">
    <div class="flex items-baseline justify-between gap-2">
        <h2 id="articles-authors-heading" class="text-text-medium font-mono text-xs tracking-[0.2em] uppercase">
            Quem escreve
        </h2>
        <span class="text-text-low font-mono text-[0.65rem]">artigos · reações</span>
    </div>

    {{-- Volume e alcance divergem no acervo: quem publicou uma vez pode ter mais
         reações que quem publicou quatro. Por isso as duas ordens são oferecidas. --}}
    <div class="flex items-center gap-1" role="group" aria-label="Ordenar pessoas">
        @foreach ([['articles', 'artigos'], ['reactions', 'reações']] as [$mode, $label])
            <button
                type="button"
                x-on:click="authorSort = '{{ $mode }}'"
                x-bind:aria-pressed="authorSort === '{{ $mode }}' ? 'true' : 'false'"
                x-bind:class="authorSort === '{{ $mode }}'
                    ? 'border-transparent bg-gradient-to-br from-primary to-secondary text-text-light'
                    : 'border-outline-dark text-text-medium hover:border-primary hover:bg-primary/10'"
                class="flex-1 cursor-pointer rounded-md border px-2 py-1.5 text-xs font-medium transition-all duration-300 active:scale-95"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <ul class="flex flex-col">
        @foreach ($authors as $author)
            <li x-bind:style="{ order: authorSort === 'reactions' ? {{ $reactionRank[$author->username] }} : {{ $loop->index }} }">
                <button
                    type="button"
                    x-on:click="toggleAuthor(@js($author->username))"
                    x-bind:aria-pressed="author === @js($author->username) ? 'true' : 'false'"
                    x-bind:class="{ 'bg-primary/16': author === @js($author->username) }"
                    class="hover:bg-primary/10 text-text-high flex w-full cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 text-start transition-colors duration-200"
                >
                    <x-portal::articles.author-avatar :name="$author->name" :avatar="$author->avatar" size="size-7" />
                    <span class="min-w-0 flex-1 truncate text-xs font-medium">{{ $author->name }}</span>
                    <span class="text-text-medium shrink-0 font-mono text-xs tabular-nums">
                        {{ $author->articleCount }}
                        <span class="text-text-low mx-0.5" aria-hidden="true">·</span>
                        {{ $author->reactions }}
                    </span>
                </button>
            </li>
        @endforeach
    </ul>

    <p class="text-text-low border-outline-low border-t pt-3 font-mono text-[0.65rem]">
        {{ count($authors) }} pessoas no acervo
    </p>
</aside>
