<section class="hp-section relative">
    <div
        class="absolute bottom-0 left-0 z-1 flex origin-top rotate-90 justify-start sm:-translate-x-[20%] sm:translate-y-32 lg:-translate-x-[5%] lg:translate-y-24 lg:rotate-0 xl:translate-y-0"
    >
        <img
            src="{{ asset('images/3pontos/logo-chain.png') }}"
            alt=""
            class="hidden h-auto w-full object-contain sm:block sm:max-w-[60%]"
        />
    </div>

    <div class="hp-container relative z-10">
        <div>
            <x-he4rt::headline align="center">
                <x-slot:badge>
                    <x-he4rt::section-title>Section name</x-he4rt::section-title>
                </x-slot>
                <x-slot:title>Palestrantes do evento</x-slot>
            </x-he4rt::headline>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:gap-8 lg:grid-cols-4">
            <x-he4rt::card class="hover:border-b-outline-light border-b-8">
                <x-slot:header class="flex flex-col items-center justify-center border-none pb-0">
                    <div class="flex flex-col items-center gap-2">
                        <x-he4rt::avatar size="2xl" src="https://avatars.githubusercontent.com/u/85951158?v=4" />
                        <x-he4rt::heading level="3" size="xs">Gabriel Vieira</x-he4rt::heading>
                        <x-he4rt::text size="sm">Product Designer</x-he4rt::text>
                    </div>
                </x-slot>
                <x-slot:description class="pb-4 text-center">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla vitae viverra nisi. Morbi at lorem
                    facilisis enim eleifend sagittis at id massa.
                </x-slot>
            </x-he4rt::card>

            <x-he4rt::card class="hover:border-b-outline-light border-b-8">
                <x-slot:header class="flex flex-col items-center justify-center border-none pb-0">
                    <div class="flex flex-col items-center gap-2">
                        <x-he4rt::avatar size="2xl" src="https://avatars.githubusercontent.com/u/85951158?v=4" />
                        <x-he4rt::heading level="3" size="xs">Gabriel Vieira</x-he4rt::heading>
                        <x-he4rt::text size="sm">Product Designer</x-he4rt::text>
                    </div>
                </x-slot>
                <x-slot:description class="pb-4 text-center">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla vitae viverra nisi. Morbi at lorem
                    facilisis enim eleifend sagittis at id massa.
                </x-slot>
            </x-he4rt::card>

            <x-he4rt::card class="hover:border-b-outline-light border-b-8">
                <x-slot:header class="flex flex-col items-center justify-center border-none pb-0">
                    <div class="flex flex-col items-center gap-2">
                        <x-he4rt::avatar size="2xl" src="https://avatars.githubusercontent.com/u/85951158?v=4" />
                        <x-he4rt::heading level="3" size="xs">Gabriel Vieira</x-he4rt::heading>
                        <x-he4rt::text size="sm">Product Designer</x-he4rt::text>
                    </div>
                </x-slot>
                <x-slot:description class="pb-4 text-center">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla vitae viverra nisi. Morbi at lorem
                    facilisis enim eleifend sagittis at id massa.
                </x-slot>
            </x-he4rt::card>

            <x-he4rt::card class="hover:border-b-outline-light border-b-8">
                <x-slot:header class="flex flex-col items-center justify-center border-none pb-0">
                    <div class="flex flex-col items-center gap-2">
                        <x-he4rt::avatar size="2xl" src="https://avatars.githubusercontent.com/u/85951158?v=4" />
                        <x-he4rt::heading level="3" size="xs">Gabriel Vieira</x-he4rt::heading>
                        <x-he4rt::text size="sm">Product Designer</x-he4rt::text>
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
