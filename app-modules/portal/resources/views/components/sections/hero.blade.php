@php
    $usersImages = [
        'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80',
        'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80',
        'https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80',
        'https://images.unsplash.com/photo-1527980965255-d3b416303d12?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80',
        'https://images.unsplash.com/photo-1527980965255-d3b416303d12?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80',
    ];
@endphp

<section class="hp-section">
    <div class="hp-container">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-12">
            <div class="space-y-6">
                <x-portal::headline size="2xl" :keywords="['potencial']">
                    <x-slot:badge>
                        <x-filament::icon icon="heroicon-o-book-open" class="h-5 w-5" />
                        Comunidade Open Source
                    </x-slot>

                    <x-slot:title>Desenvolva seu potencial na comunidade</x-slot>

                    <x-slot:description>
                        Uma comunidade de desenvolvedores dedicada a ajudar iniciantes a se tornarem profissionais
                        através de projetos, mentorias e networking.
                    </x-slot>
                    <x-slot:actions>
                        <x-portal::button icon="heroicon-s-chevron-right">Começar agora</x-portal::button>

                        <x-portal::button icon="heroicon-s-chevron-right" variant="outline">
                            Explorar projetos
                        </x-portal::button>
                    </x-slot>
                </x-portal::headline>
                <x-portal::avatar-stack :images="$usersImages" limit="5">
                    Mais de 9.000 desenvolvedores já fazem parte
                </x-portal::avatar-stack>
            </div>
            <div>Image</div>
        </div>
    </div>
</section>
