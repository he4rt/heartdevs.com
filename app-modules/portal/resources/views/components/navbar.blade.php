@php
    $links = [
        ['label' => 'Comunidade', 'anchor' => '#community'],
        ['label' => 'Sobre', 'anchor' => '#about'],
        ['label' => 'Projetos', 'anchor' => '#projects'],
        ['label' => 'Depoimentos', 'anchor' => '#testimonials'],
        ['label' => 'Contato', 'anchor' => '#contact'],
    ];
@endphp

<nav class="w-full px-8">
    <div class="container mx-auto flex items-center justify-between py-4 md:py-6">
        <x-portal::logo size="sm" />

        <div class="hidden items-center gap-8 md:flex">
            @foreach ($links as $link)
                <a
                    href="{{ $link['anchor'] }}"
                    class="relative pb-1 text-sm font-medium text-gray-400 transition-colors after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:w-0 after:rounded-full after:bg-[var(--primary)] after:transition-all after:duration-200 hover:text-white hover:after:w-full focus-visible:text-white focus-visible:after:w-full"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        <div class="flex items-center gap-4">
            <x-he4rt::button href="/app" size="sm" variant="outline" class="hidden md:inline-flex">
                Área do Usuário
            </x-he4rt::button>

            <x-he4rt::button
                href="https://discord.gg/he4rt"
                size="xs"
                icon="fab-discord"
                iconPosition="leading"
                iconOnly
                class="w-auto! md:hidden"
            >
                Discord
            </x-he4rt::button>

            <div class="hidden md:block">
                <x-he4rt::button href="https://discord.gg/he4rt" size="sm" icon="fab-discord" iconPosition="leading">
                    Discord
                </x-he4rt::button>
            </div>
        </div>
    </div>
</nav>
