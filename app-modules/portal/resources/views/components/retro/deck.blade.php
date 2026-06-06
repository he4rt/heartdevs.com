@props (['stateKey' => ''])
<div class="retro">
    <div class="atmo"></div>
    <svg class="grain">
        <filter id="retro-noise">
            <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="3" stitchTiles="stitch" />
        </filter>
        <rect width="100%" height="100%" filter="url(#retro-noise)" />
    </svg>

    {{ $filters ?? '' }}

    <div
        class="deck-shell"
        wire:key="deck-{{ $stateKey }}"
        x-data="{
            active: 0,
            total: 0,
            label: '',
            slides: [],
            init() {
                this.slides = Array.from($el.querySelectorAll('.slide'));
                this.total = this.slides.length;
                this.slides.forEach((sl) =>
                    sl
                        .querySelectorAll('[data-anim]')
                        .forEach((el, k) => (el.style.animationDelay = 110 + Math.min(k, 14) * 60 + 'ms')),
                );
                this.go(0);
            },
            go(i) {
                this.active = Math.max(0, Math.min(i, this.total - 1));
                this.slides.forEach((s, k) => {
                    s.classList.toggle('active', k === this.active);
                    s.classList.toggle('past', k < this.active);
                    if (k === this.active) s.scrollTop = 0;
                });
                this.label = this.slides[this.active] ? this.slides[this.active].dataset.label : '';
            },
        }"
        @keydown.window="
            if ($event.key === 'ArrowRight') {
                go(active + 1);
                $event.preventDefault();
            } else if ($event.key === 'ArrowLeft') {
                go(active - 1);
                $event.preventDefault();
            }
        "
    >
        <div class="progress">
            <div class="bar" :style="'width: ' + (total ? ((active + 1) / total) * 100 : 0) + '%'"></div>
        </div>

        <button type="button" class="filter-fab" @click="$dispatch('retro-open-filters')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" /></svg>
            Filtros
        </button>

        <div class="deck">{{ $slot }}</div>

        <nav class="navbar">
            <div class="counter">
                <b x-text="String(active + 1).padStart(2, '0')"></b> /
                <span x-text="String(total).padStart(2, '0')"></span>
                <span class="slabel">· <span x-text="label"></span></span>
            </div>
            <div class="dots">
                <template x-for="i in total" :key="i">
                    <button type="button" class="dot" :class="active === i - 1 ? 'on' : ''" @click="go(i - 1)"></button>
                </template>
            </div>
            <button type="button" class="navbtn" @click="go(active - 1)" :disabled="active === 0">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6" /></svg>
            </button>
            <button type="button" class="navbtn" @click="go(active + 1)" :disabled="active >= total - 1">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6" /></svg>
            </button>
        </nav>
    </div>
</div>
