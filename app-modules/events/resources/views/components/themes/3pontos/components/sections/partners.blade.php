<section class="hp-section">
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
            class="inline-flex w-full flex-nowrap overflow-hidden mask-r-from-95% mask-r-to-99% mask-l-from-95% mask-l-to-99%"
        >
            <ul
                x-ref="partners"
                class="animate-infinite-scroll flex items-center justify-center md:justify-start [&_li]:mx-8"
            >
                <li class="w-[280px]">
                    <img src="./facebook.svg" alt="Facebook" />
                </li>
                <li class="w-[280px]">
                    <img src="./disney.svg" alt="Disney" />
                </li>
                <li class="w-[280px]">
                    <img src="./airbnb.svg" alt="Airbnb" />
                </li>
                <li class="w-[280px]">
                    <img src="./apple.svg" alt="Apple" />
                </li>
                <li class="w-[280px]">
                    <img src="./spark.svg" alt="Spark" />
                </li>
                <li class="w-[280px]">
                    <img src="./samsung.svg" alt="Samsung" />
                </li>
                <li class="w-[280px]">
                    <img src="./quora.svg" alt="Quora" />
                </li>
                <li class="w-[280px]">
                    <img src="./sass.svg" alt="Sass" />
                </li>
            </ul>
        </div>
    </div>
</section>
