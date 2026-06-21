<div class="mx-auto flex w-full max-w-[480px] flex-col gap-3 px-6 py-16">
    @foreach ($links as $link)
        <a
            href="{{ $link->url }}"
            target="_blank"
            rel="noopener noreferrer"
            class="border-outline-low bg-elevation-01dp flex items-center gap-4 rounded-xl border px-5 py-4 text-white"
        >
            <x-filament::icon :icon="$link->icon" class="h-6 w-6 shrink-0" />
            <span class="flex-1 text-left font-medium">{{ $link->label }}</span>
        </a>
    @endforeach
</div>
