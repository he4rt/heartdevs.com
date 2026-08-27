@props([
    'article',
    'index',
])

<article
    x-show="isVisible({{ $index }})"
    class="border-outline-low bg-elevation-01dp hover:border-primary relative flex flex-col gap-3 rounded-lg border p-4 transition-[border-color,transform] duration-300 hover:scale-[1.02] motion-reduce:transition-none motion-reduce:hover:scale-100 [.is-list_&]:flex-row [.is-list_&]:items-start [.is-list_&]:gap-4 [.is-list_&]:hover:scale-100"
>
    @if ($article->coverImage)
        <img
            src="{{ $article->coverImage }}"
            alt=""
            loading="lazy"
            decoding="async"
            class="aspect-video w-full shrink-0 rounded-sm object-cover [.is-list_&]:w-28"
        />
    @else
        <x-portal::articles.cover-fallback class="aspect-video w-full shrink-0 rounded-sm [.is-list_&]:w-28" />
    @endif

    <div class="flex min-w-0 flex-1 flex-col gap-2">
        @if ($article->tags !== [])
            <ul class="flex flex-wrap gap-1.5">
                @foreach (array_slice($article->tags, 0, 2) as $tag)
                    <li
                        class="border-primary/32 bg-primary/5 text-text-medium rounded-lg border px-2 py-0.5 font-mono text-[0.65rem]"
                    >
                        #{{ $tag }}
                    </li>
                @endforeach
            </ul>
        @endif

        <h3 class="text-text-high line-clamp-3 text-sm leading-snug font-semibold">
            <a
                href="{{ $article->url }}"
                target="_blank"
                rel="noopener noreferrer"
                class="after:absolute after:inset-0 focus-visible:outline-none"
            >
                {{ $article->title }}
            </a>
        </h3>

        {{-- A descrição já vem truncada da fonte; o clamp evita que o "…" dela pareça quebra de layout. --}}
        <p class="text-text-medium line-clamp-2 text-xs leading-relaxed">{{ $article->description }}</p>

        <div class="text-text-medium mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 pt-1 text-[0.7rem]">
            <span class="flex min-w-0 items-center gap-1.5">
                <x-portal::articles.author-avatar :name="$article->authorName" :avatar="$article->authorAvatar" size="size-4" />
                <span class="text-text-high truncate font-medium">{{ $article->authorName }}</span>
            </span>
            <span class="ms-auto flex items-center gap-2.5 font-mono tabular-nums">
                <span title="reações">♥ {{ $article->reactions }}</span>
                <span title="comentários">💬 {{ $article->comments }}</span>
                <span title="tempo de leitura">{{ $article->readingMinutes }} min</span>
            </span>
        </div>
    </div>
</article>
