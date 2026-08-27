@props(['article'])

{{-- Acima da grade e na largura dela: o destaque abre a listagem em vez de
     disputar a primeira dobra com o título da página. --}}
<article
    class="border-outline-low bg-elevation-01dp hover:border-primary relative mb-6 flex flex-col gap-5 rounded-lg border p-4 transition-colors duration-300 sm:flex-row sm:items-center"
>
    @if ($article->coverImage)
        <img
            src="{{ $article->coverImage }}"
            alt=""
            loading="lazy"
            decoding="async"
            class="aspect-video w-full shrink-0 rounded-sm object-cover sm:w-72 lg:w-80"
        />
    @else
        <x-portal::articles.cover-fallback class="aspect-video w-full shrink-0 rounded-sm sm:w-72 lg:w-80" />
    @endif

    <div class="flex min-w-0 flex-col gap-2.5">
        <span
            class="border-primary/32 bg-primary/5 text-text-high w-fit rounded-full border px-3 py-1 font-mono text-[0.65rem] tracking-wide"
        >
            ★ destaque · mais reagido dos últimos 12 meses
        </span>

        <h2 class="text-text-high line-clamp-2 text-xl leading-snug font-semibold lg:text-2xl">
            <a
                href="{{ $article->url }}"
                target="_blank"
                rel="noopener noreferrer"
                class="after:absolute after:inset-0 focus-visible:outline-none"
            >
                {{ $article->title }}
            </a>
        </h2>

        @if ($article->description !== '')
            <p class="text-text-medium line-clamp-2 max-w-2xl text-sm leading-relaxed">{{ $article->description }}</p>
        @endif

        <div class="text-text-medium flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
            <span class="flex items-center gap-2">
                <x-portal::articles.author-avatar :name="$article->authorName" :avatar="$article->authorAvatar" size="size-5" />
                <span class="text-text-high font-medium">{{ $article->authorName }}</span>
            </span>
            <span class="font-mono tabular-nums">♥ {{ $article->reactions }}</span>
            <span class="font-mono tabular-nums">{{ $article->readingMinutes }} min</span>
            <span class="font-mono">{{ $article->publishedLabel() }}</span>
        </div>
    </div>
</article>
