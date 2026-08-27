@props([
    'logoPath' => 'images/logo.svg',
    'logoSize' => 'md',
    'description' => '',
    'columns' => [],
    'company' => 'He4rtDevs',
])

@php
    $totalVisualColumns = collect($columns)
        ->map(fn ($links) => ceil(count($links) / 3))
        ->sum();

    $gridClass = 'sm:grid-cols-' . $totalVisualColumns;
@endphp

<footer class="hp-footer">
    <div class="hp-footer-container">
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-[2fr_1.5fr] sm:gap-24">
            <div>
                <x-he4rt::logo :size="$logoSize" :path="$logoPath" />
                <x-he4rt::text size="md" class="mt-4">
                    {{ $description }}
                </x-he4rt::text>
            </div>

            @if (count($columns) > 0)
                <div
                    @class([
                        'grid grid-cols-1 gap-4',
                        $gridClass,
                    ])
                >
                    @foreach ($columns as $title => $links)
                        @foreach (array_chunk($links, 3, true) as $index => $chunk)
                            <div class="hp-footer-col">
                                <x-he4rt::heading
                                    level="4"
                                    size="sm"
                                    class="hp-footer-heading mb-4 {{ $index > 0 ? 'hidden sm:block sm:invisible' : '' }}"
                                >
                                    {{ $title }}
                                </x-he4rt::heading>

                                <ul class="hp-footer-list">
                                    @foreach ($chunk as $label => $url)
                                        <li>
                                            <a
                                                href="{{ $url }}"
                                                class="hp-footer-link hover:text-text-high transition-colors"
                                            >
                                                {{ $label }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endif
        </div>

        <hr class="border-outline-low my-8" />

        <div class="hp-footer-bottom">
            <span>© {{ date('Y') }} {{ $company }}</span>
            <span>Todos os direitos reservados</span>
        </div>
    </div>
</footer>
