{{-- PROTOTYPE — Floating variant switcher bar --}}
@props ([ 'current' => 'A', 'variants' => ['A' => 'Card Feed', 'B' => 'Dense Stream', 'C' => 'Timeline Rail'] ])

@php
    $keys = array_keys($variants);
    $currentIdx = array_search($current, $keys, true);
    $prevIdx = ($currentIdx - 1 + count($keys)) % count($keys);
    $nextIdx = ($currentIdx + 1) % count($keys);
@endphp

<div
    x-data="{
        current: '{{ $current }}',
        variants: @js($keys),
        names: @js($variants),
        get currentIdx() { return this.variants.indexOf(this.current) },
        prev() {
            const idx = (this.currentIdx - 1 + this.variants.length) % this.variants.length;
            this.navigate(this.variants[idx]);
        },
        next() {
            const idx = (this.currentIdx + 1) % this.variants.length;
            this.navigate(this.variants[idx]);
        },
        navigate(v) {
            const url = new URL(window.location);
            url.searchParams.set('variant', v);
            window.location = url.toString();
        },
        handleKey(e) {
            if (['INPUT', 'TEXTAREA'].includes(e.target.tagName) || e.target.isContentEditable) return;
            if (e.key === 'ArrowLeft') { e.preventDefault(); this.prev(); }
            if (e.key === 'ArrowRight') { e.preventDefault(); this.next(); }
        }
    }"
    x-init="document.addEventListener('keydown', (e) => handleKey(e))"
    class="fixed bottom-4 left-1/2 z-[9999] flex -translate-x-1/2 items-center gap-2 rounded-full border border-gray-700 bg-gray-900 px-3 py-2 text-sm shadow-2xl shadow-black/50 dark:bg-gray-800"
>
    <button
        @click="prev()"
        class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-800 text-gray-300 transition hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600"
    >
        <x-heroicon-s-chevron-left class="h-4 w-4" />
    </button>

    <div class="flex min-w-[180px] items-center justify-center gap-2 px-3">
        @foreach ($keys as $key)
            <button
                @click="navigate('{{ $key }}')"
                @class ([
                    'w-2 h-2 rounded-full transition',
                    'bg-primary-500 scale-125' => $key === $current,
                    'bg-gray-600 hover:bg-gray-500' => $key !== $current
                ])
            ></button>
        @endforeach
        <span class="ml-2 font-mono text-xs text-white">
            <span class="font-bold">{{ $current }}</span>
            <span class="ml-1 text-gray-400">— {{ $variants[$current] }}</span>
        </span>
    </div>

    <button
        @click="next()"
        class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-800 text-gray-300 transition hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600"
    >
        <x-heroicon-s-chevron-right class="h-4 w-4" />
    </button>

    <div class="ml-2 border-l border-gray-700 pl-2 font-mono text-[10px] text-gray-500">
        PROTOTYPE<br />← → to switch
    </div>
</div>
