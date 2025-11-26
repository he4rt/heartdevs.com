<section class="hp-section" id="partners">
    <div class="hp-container">
        <div>
            <x-he4rt::headline align="center">
                <x-slot:badge>
                    <x-he4rt::section-title>Parceiros</x-he4rt::section-title>
                </x-slot>
                <x-slot:title>Nossos parceiros</x-slot>
                <x-slot:description>
                    Parceiros que se unem a nós para construir impacto de verdade, com valor e propósito.
                </x-slot>
            </x-he4rt::headline>
        </div>

        <div
            x-data="{}"
            x-init="
                $nextTick(() => {
                    let ul = $refs.partners
                    ul.insertAdjacentHTML('afterend', ul.outerHTML)
                    ul.nextSibling.setAttribute('aria-hidden', 'true')
                })
            "
            class="mt-8 inline-flex w-full flex-nowrap overflow-hidden mask-r-from-95% mask-r-to-99% mask-l-from-95% mask-l-to-99%"
        >
            <ul
                x-ref="partners"
                class="animate-infinite-scroll flex items-center justify-center md:justify-start [&_img]:cursor-pointer [&_img]:grayscale [&_img]:transition-all [&_img]:duration-300 [&_img]:hover:grayscale-0 [&_li]:mx-8"
            >
                <li class="flex h-24 w-[300px] items-center justify-center">
                    <img class="h-full w-full object-contain" src="{{ asset('images/logo.svg') }}" alt="He4rt" />
                </li>

                <li class="flex h-24 w-[300px] items-center justify-center">
                    <img
                        class="h-full w-full object-contain"
                        src="{{ asset('images/3pontos/partners/firece-logo.svg') }}"
                        alt="Firece"
                    />
                </li>

                <li class="flex h-24 w-[300px] items-center justify-center">
                    <img
                        class="h-full w-full object-contain"
                        src="{{ asset('images/3pontos/partners/flamma-logo.svg') }}"
                        alt="Flamma"
                    />
                </li>

                <li class="flex h-24 w-[300px] items-center justify-center">
                    <img
                        class="h-full w-full object-contain"
                        src="{{ asset('images/3pontos/partners/ipe-logo.svg') }}"
                        alt="Ipe Crowd"
                    />
                </li>

                <li class="flex h-24 w-[300px] items-center justify-center">
                    <img
                        class="h-full w-full object-contain"
                        src="{{ asset('images/3pontos/partners/betel-logo.png') }}"
                        alt="Betel"
                    />
                </li>
            </ul>
        </div>
    </div>
</section>
