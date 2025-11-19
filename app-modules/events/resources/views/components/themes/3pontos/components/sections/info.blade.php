<section class="hp-section relative z-1">
    <div class="hp-container">
        <div class="grid grid-cols-1 gap-y-8 lg:grid-cols-2">
            <div class="h-full w-full bg-red-500">map</div>
            <div class="flex flex-col items-center justify-center lg:pl-16">
                <x-he4rt::headline>
                    <x-slot:title>Informações sobre o evento</x-slot>
                    <x-slot:subtitle class="gap-8">
                        <div class="flex gap-2">
                            <x-he4rt::icon
                                :interactive="false"
                                icon="heroicon-o-map-pin"
                                class="text-icon-light bg-transparent p-0!"
                            />
                            <x-he4rt::text class="font-bold">
                                Endereço: R. Aspicuelta, 422 - Vila Madalena, São Paulo - SP, 05416-011
                            </x-he4rt::text>
                        </div>
                        <div class="flex gap-2">
                            <x-he4rt::icon
                                :interactive="false"
                                icon="heroicon-o-clock"
                                class="text-icon-light bg-transparent p-0!"
                            />
                            <x-he4rt::text class="font-bold">Horário: Sábado (3/12) - 14:00 até 19:00</x-he4rt::text>
                        </div>
                        <div class="flex gap-8">
                            <x-he4rt::icon icon="fab-instagram" class="text-icon-light bg-transparent p-0!" />
                            <x-he4rt::icon icon="fab-x-twitter" class="text-icon-light bg-transparent p-0!" />
                            <x-he4rt::icon icon="fab-linkedin" class="text-icon-light bg-transparent p-0!" />
                        </div>
                    </x-slot>
                    <x-slot:description>
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur lobortis molestie blandit.
                        Nunc iaculis purus sollicitudin laoreet dapibus
                    </x-slot>
                    <x-slot:actions>
                        <x-he4rt::button>Button</x-he4rt::button>
                    </x-slot>
                </x-he4rt::headline>
            </div>
        </div>
    </div>
</section>
