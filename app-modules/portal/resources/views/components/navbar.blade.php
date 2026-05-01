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
    <div class="container mx-auto flex items-center justify-between py-6">
        <x-portal::logo size="sm" />

        <div class="hidden items-center gap-8 md:flex">
            @foreach ($links as $link)
                <a
                    href="{{ $link['anchor'] }}"
                    class="text-sm font-medium text-gray-400 transition-colors hover:text-white"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        <x-he4rt::button href="https://discord.gg/he4rt" size="sm" icon="fab-discord" iconPosition="leading">
            Discord
        </x-he4rt::button>
    </div>
</nav>
