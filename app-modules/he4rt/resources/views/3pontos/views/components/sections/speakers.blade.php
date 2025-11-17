<section class="hp-section">
    <div class="hp-container">
        <div>
            <x-he4rt::headline align="center">
                <x-slot:badge>
                    <x-3pontos::section-title>Section name</x-3pontos::section-title>
                </x-slot>
                <x-slot:title>Palestrantes do evento</x-slot>
            </x-he4rt::headline>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:gap-8 lg:grid-cols-4">
            <x-he4rt::card :interactive="false" class="border-b-8">
                <x-slot:header class="flex flex-col items-center justify-center border-none pb-0">
                    <div class="flex flex-col items-center gap-2">
                        <x-he4rt::avatar size="2xl" src="https://avatars.githubusercontent.com/u/85951158?v=4" />
                        <x-he4rt::heading level="3" size="xs">Gabriel Vieira</x-he4rt::heading>
                        <span class="text-text-medium text-sm">Product Designer</span>
                    </div>
                </x-slot>
                <x-slot:description class="pb-4 text-center">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla vitae viverra nisi. Morbi at lorem
                    facilisis enim eleifend sagittis at id massa.
                </x-slot>
            </x-he4rt::card>

            <x-he4rt::card :interactive="false" class="border-b-8">
                <x-slot:header class="flex flex-col items-center justify-center border-none pb-0">
                    <div class="flex flex-col items-center gap-2">
                        <x-he4rt::avatar size="2xl" src="https://avatars.githubusercontent.com/u/85951158?v=4" />
                        <x-he4rt::heading level="3" size="xs">Gabriel Vieira</x-he4rt::heading>
                        <span class="text-text-medium text-sm">Product Designer</span>
                    </div>
                </x-slot>
                <x-slot:description class="pb-4 text-center">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla vitae viverra nisi. Morbi at lorem
                    facilisis enim eleifend sagittis at id massa.
                </x-slot>
            </x-he4rt::card>

            <x-he4rt::card :interactive="false" class="border-b-8">
                <x-slot:header class="flex flex-col items-center justify-center border-none pb-0">
                    <div class="flex flex-col items-center gap-2">
                        <x-he4rt::avatar size="2xl" src="https://avatars.githubusercontent.com/u/85951158?v=4" />
                        <x-he4rt::heading level="3" size="xs">Gabriel Vieira</x-he4rt::heading>
                        <span class="text-text-medium text-sm">Product Designer</span>
                    </div>
                </x-slot>
                <x-slot:description class="pb-4 text-center">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla vitae viverra nisi. Morbi at lorem
                    facilisis enim eleifend sagittis at id massa.
                </x-slot>
            </x-he4rt::card>

            <x-he4rt::card :interactive="false" class="border-b-8">
                <x-slot:header class="flex flex-col items-center justify-center border-none pb-0">
                    <div class="flex flex-col items-center gap-2">
                        <x-he4rt::avatar size="2xl" src="https://avatars.githubusercontent.com/u/85951158?v=4" />
                        <x-he4rt::heading level="3" size="xs">Gabriel Vieira</x-he4rt::heading>
                        <span class="text-text-medium text-sm">Product Designer</span>
                    </div>
                </x-slot>
                <x-slot:description class="pb-4 text-center">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla vitae viverra nisi. Morbi at lorem
                    facilisis enim eleifend sagittis at id massa.
                </x-slot>
            </x-he4rt::card>
        </div>
    </div>
</section>
