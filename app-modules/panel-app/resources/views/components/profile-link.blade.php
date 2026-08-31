@props (['user' => null])

@use('He4rt\Profile\Support\PublicProfileCache')

@php
    $isLinkable = $user !== null && $user->banned_at === null && filled($user->username);
@endphp

@if ($isLinkable)
    <span
        class="contents"
        x-data="{
            url: @js(route('profile.card', $user->username)),
            ttl: @js(PublicProfileCache::TTL_SECONDS * 1000),
            open: false,
            loading: false,
            html: null,
            hoverable: window.matchMedia('(hover: hover)').matches,
            cardStyle: '',
            openTimer: null,
            closeTimer: null,

            scheduleOpen() {
                if (! this.hoverable) return

                clearTimeout(this.openTimer)
                clearTimeout(this.closeTimer)
                this.openTimer = setTimeout(() => this.show(), 400)
            },

            scheduleClose() {
                clearTimeout(this.openTimer)
                clearTimeout(this.closeTimer)
                this.closeTimer = setTimeout(() => this.open = false, 300)
            },

            cancelClose() {
                clearTimeout(this.closeTimer)
            },

            close() {
                clearTimeout(this.openTimer)
                clearTimeout(this.closeTimer)
                this.open = false
            },

            position() {
                if (! this.open) return

                const trigger = this.$refs.trigger.getBoundingClientRect()
                const width = this.$refs.card.offsetWidth || 320
                const height = this.$refs.card.offsetHeight || 200
                const left = Math.max(8, Math.min(trigger.left, window.innerWidth - width - 8))
                const fitsBelow = trigger.bottom + 8 + height <= window.innerHeight - 8
                const fitsAbove = trigger.top - 8 - height >= 8
                const top = fitsBelow || ! fitsAbove ? trigger.bottom + 8 : trigger.top - 8 - height

                this.cardStyle = 'left: ' + left + 'px; top: ' + Math.max(8, top) + 'px'
            },

            reposition() {
                this.$nextTick(() => this.position())
            },

            cards() {
                window.__profileCards ??= new Map()

                return window.__profileCards
            },

            cached() {
                const entry = this.cards().get(this.url)

                if (entry === undefined) return null

                if (entry.expiresAt <= Date.now()) {
                    this.cards().delete(this.url)

                    return null
                }

                return entry
            },

            request() {
                const cached = this.cached()

                if (cached !== null) return cached

                const entry = { expiresAt: Date.now() + this.ttl, html: null, promise: null }

                entry.promise = fetch(this.url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then((response) => (response.ok ? response.text() : null))
                    .catch(() => null)
                    .then((html) => {
                        if (html === null) {
                            this.cards().delete(this.url)

                            return null
                        }

                        entry.html = html

                        return html
                    })

                this.cards().set(this.url, entry)

                return entry
            },

            async show() {
                if (! this.hoverable) return

                this.open = true
                this.reposition()

                if (this.html !== null) return

                const entry = this.request()

                if (entry.html !== null) {
                    this.html = entry.html
                    this.reposition()

                    return
                }

                this.loading = true

                const html = await entry.promise

                this.loading = false

                if (html === null) {
                    this.open = false

                    return
                }

                this.html = html
                this.reposition()
            },
        }"
        x-on:keydown.escape.window="close()"
        x-on:scroll.window.passive="reposition()"
        x-on:resize.window.passive="reposition()"
    >
        <a
            href="{{ route('profile.public', $user->username) }}"
            x-ref="trigger"
            x-on:mouseenter="scheduleOpen()"
            x-on:mouseleave="scheduleClose()"
            x-on:focus="show()"
            x-on:blur="scheduleClose()"
            {{ $attributes }}
        >
            {{ $slot }}
        </a>

        <div
            x-ref="card"
            x-cloak
            x-show="open"
            x-bind:style="cardStyle"
            x-transition.opacity.duration.150ms
            x-on:mouseenter="cancelClose()"
            x-on:mouseleave="scheduleClose()"
            class="fixed z-50 w-80 max-w-[calc(100vw-1rem)]"
        >
            <template x-if="loading">
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-lg dark:border-white/10 dark:bg-gray-900"
                >
                    <div class="flex items-start gap-3">
                        <div class="h-12 w-12 shrink-0 animate-pulse rounded-full bg-gray-200 dark:bg-white/10"></div>
                        <div class="flex-1 space-y-2 pt-1">
                            <div class="h-3 w-2/3 animate-pulse rounded bg-gray-200 dark:bg-white/10"></div>
                            <div class="h-2.5 w-1/3 animate-pulse rounded bg-gray-200 dark:bg-white/10"></div>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="h-2.5 w-full animate-pulse rounded bg-gray-200 dark:bg-white/10"></div>
                        <div class="h-2.5 w-1/2 animate-pulse rounded bg-gray-200 dark:bg-white/10"></div>
                    </div>
                </div>
            </template>

            <template x-if="html !== null">
                <div x-html="html"></div>
            </template>
        </div>
    </span>
@else
    <span {{ $attributes }}>{{ $slot }}</span>
@endif
