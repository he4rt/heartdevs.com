<section class="hp-section bg-elevation-01dp border-outline-dark relative min-h-0 overflow-hidden border-t border-b">
    <div class="absolute right-0 bottom-0 z-1 h-1/2 w-1/2 translate-x-1/2 lg:-translate-y-1/2">
        <img src="{{ asset('images/3pontos/3pontos-ball.svg') }}" alt="" class="h-auto w-full object-cover" />
    </div>

    <div class="hp-container relative z-10">
        <div>
            <x-he4rt::headline :keywords="['Lorem', 'ipsum']">
                <x-slot:badge>
                    <x-3pontos::section-title>Section name</x-3pontos::section-title>
                </x-slot>
                <x-slot:title>Lorem ipsum um pouco da 3 pontos</x-slot>
                <x-slot:description>
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur lobortis molestie blandit. Nunc
                    iaculis purus sollicitudin laoreet dapibus. In non elementum sem, id volutpat velit. Fusce id
                    ultricies magna, sit amet suscipit est. Maecenas ut enim ac lacus blandit laoreet id sed sem.
                    Vestibulum et efficitur nisi. Mauris eu diam vitae nunc porta facilisis. Cras vitae arcu odio.
                    Pellentesque feugiat suscipit.
                </x-slot>
                <x-slot:actions>
                    <x-he4rt::button>Button</x-he4rt::button>
                </x-slot>
            </x-he4rt::headline>
        </div>
    </div>
</section>
