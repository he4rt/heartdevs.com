@props([
    'pages',
    'currentPage' => null,
])

<aside class="w-64 overflow-y-auto border-r border-zinc-800 bg-zinc-900">
    <div class="p-6">
        <h3 class="mb-4 text-lg font-semibold text-zinc-100">Documentação</h3>

        <flux:navlist>
            @foreach ($pages as $page)
                <flux:navlist.item
                    href="/docs/{{ $page['slug'] }}"
                    wire:navigate
                    :current="$currentPage === $page['slug']"
                >
                    {{ $page['title'] }}
                </flux:navlist.item>
            @endforeach
        </flux:navlist>
    </div>
</aside>
