@php
    $groups = [
        'Comunidade' => [
            'Artigos' => route('articles', absolute: false),
            'Galeria' => route('gallery', absolute: false),
            'Redes' => route('social-links', absolute: false),
        ],
        'Legal' => [
            'Política de privacidade' => route('privacy-policy', absolute: false),
            'Termos de uso' => route('terms-of-service', absolute: false),
        ],
    ];
@endphp

<footer class="border-outline-low mt-auto border-t">
    <div class="hp-page flex flex-col gap-8 py-10 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex max-w-xs flex-col gap-3">
            <x-portal::logo size="sm" />
            <p class="text-text-medium text-sm leading-relaxed">
                Comunidade brasileira de pessoas desenvolvedoras, desde 2018.
            </p>
        </div>

        <div class="flex gap-12">
            @foreach ($groups as $title => $links)
                <nav aria-label="{{ $title }}" class="flex flex-col gap-3">
                    <p class="text-text-high font-mono text-xs tracking-[0.2em] uppercase">{{ $title }}</p>
                    <ul class="flex flex-col gap-2 text-sm">
                        @foreach ($links as $label => $href)
                            <li>
                                <a href="{{ $href }}" class="text-text-medium hover:text-text-high transition-colors">
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach
        </div>
    </div>

    <div class="border-outline-low border-t">
        <p class="hp-page text-text-low py-4 text-xs">© {{ date('Y') }} He4rt Developers</p>
    </div>
</footer>
