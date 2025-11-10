@props([
    'title' => 'Documentação - He4rt Bot API',
    'description' => null,
])

<x-layouts.app :title="$title">
    <div class="min-h-screen bg-zinc-950 text-zinc-100">
        <x-partials.docs-header />

        <div class="flex">
            {{ $sidebar ?? '' }}

            <main class="flex-1 p-8">
                <div class="mx-auto max-w-4xl">
                    @if ($description)
                        <p class="mb-6 text-lg text-zinc-400">{{ $description }}</p>
                    @endif

                    {{ $slot }}
                </div>

                <x-partials.docs-footer />
            </main>
        </div>
    </div>
</x-layouts.app>
