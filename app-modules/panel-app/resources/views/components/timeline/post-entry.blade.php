@props (['content'])

<div class="px-4 pb-3">
    <div class="prose prose-sm dark:prose-invert max-w-none break-words">
        {!! str($content->content)->markdown()->sanitizeHtml() !!}
    </div>

    @if ($content->getMedia('images')->isNotEmpty())
        <div class="mt-3 grid gap-2 {{ $content->getMedia('images')->count() > 1 ? 'grid-cols-2' : 'grid-cols-1' }}">
            @foreach ($content->getMedia('images') as $media)
                <img
                    src="{{ $media->getUrl() }}"
                    alt=""
                    class="max-h-80 w-full rounded-lg border border-gray-200 object-cover dark:border-white/10"
                    loading="lazy"
                />
            @endforeach
        </div>
    @endif
</div>
