<div class="fi-login-split flex min-h-dvh w-full overflow-hidden bg-zinc-950">
    {{-- Left: Brand panel --}}
    <div
        class="relative hidden w-[480px] shrink-0 overflow-hidden lg:flex lg:flex-col lg:items-center lg:justify-center"
        style="background: linear-gradient(165deg, #782bf1 0%, #3b1578 45%, #18181b 100%)"
    >
        {{-- Glow accents --}}
        <div
            class="absolute top-0 right-0 h-64 w-64 rounded-full blur-3xl"
            style="background: #9b59f5; animation: login-pulse-glow 4s ease-in-out infinite"
        ></div>
        <div
            class="absolute bottom-0 left-0 h-48 w-48 rounded-full blur-3xl"
            style="background: #782bf1; animation: login-pulse-glow 4s ease-in-out infinite 2s"
        ></div>

        {{-- Landing logo as background watermark --}}
        <div class="absolute inset-0 flex items-center justify-center opacity-[0.06]">
            <img src="{{ asset('images/landingLogo.svg') }}" alt="" class="h-[420px] w-auto" aria-hidden="true" />
        </div>

        {{-- Content --}}
        <div class="relative z-10 flex flex-col items-center px-12 text-center">
            <div class="animate-heartbeat">
                <svg
                    class="mb-8 h-32 w-auto drop-shadow-[0_0_35px_rgba(120,43,241,0.5)]"
                    viewBox="0 0 600 513"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M445.237 0.00033551C424.91 -0.0347431 404.777 3.89304 385.996 11.5576C367.216 19.2221 350.159 30.4719 335.808 44.6594L153.391 224.398L116.915 188.45C111.983 183.761 108.048 178.15 105.341 171.946C102.633 165.741 101.207 159.067 101.145 152.314C101.084 145.56 102.388 138.862 104.983 132.611C107.577 126.359 111.409 120.68 116.255 115.904C121.101 111.128 126.864 107.352 133.207 104.795C139.55 102.239 146.347 100.953 153.2 101.014C160.052 101.074 166.825 102.48 173.12 105.148C179.416 107.816 185.109 111.694 189.867 116.555L262.856 44.6594C233.71 16.6424 194.537 1.07109 153.824 1.31914C113.11 1.56719 74.1349 17.6146 45.3431 45.9846C16.5513 74.3546 0.261527 112.762 0.0031216 152.886C-0.255283 193.01 15.5385 231.618 43.9626 260.346L153.391 368.189L511.948 14.8274C491.12 5.01981 468.32 -0.0474973 445.237 0.00033551Z"
                        fill="white"
                    />
                    <path
                        d="M584.9 86.7579L445.237 224.433L408.76 188.45L335.808 260.345L372.284 296.293L226.379 440.084L299.332 512.015L554.665 260.381C577.296 238.07 592.355 209.395 597.769 178.303C603.183 147.21 598.687 115.228 584.9 86.7579Z"
                        fill="white"
                    />
                </svg>
            </div>

            <h1 class="mb-2 text-2xl font-bold tracking-tight text-white">He4rt Developers</h1>
            <p class="text-sm leading-relaxed text-white/60">Comunidade brasileira de desenvolvedores.<br />
            Aprenda, compartilhe e cresça.</p>
        </div>

        <div
            class="absolute right-0 bottom-0 left-0 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"
        ></div>
    </div>

    {{-- Right: Login form --}}
    <div class="relative flex flex-1 items-center justify-center p-8 lg:p-12">
        {{-- Background subtle glow for depth --}}
        <div class="absolute top-1/2 left-1/2 h-[600px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-purple-600/5 blur-[120px]"></div>

        <div class="relative z-10 w-full max-w-sm">
            {{-- Mobile logo --}}
            <div class="mb-10 flex items-center justify-center lg:hidden">
                <img
                    src="{{ asset('images/logo.svg') }}"
                    alt="He4rt Developers"
                    class="h-14 w-auto animate-pulse"
                />
            </div>

            <div class="mb-10">
                <h2 class="text-3xl font-bold tracking-tight text-white">Entrar</h2>
                <p class="mt-3 text-zinc-400">Acesse sua conta He4rt Developers</p>
            </div>

            {{-- OAuth --}}
            <div class="grid gap-4">
                <a
                    href="{{ route('oauth.redirect', ['tenant' => request()->getHost(), 'panel' => $panelId, 'provider' => 'discord']) }}"
                    class="group flex items-center justify-center gap-3 rounded-xl bg-[#5865F2] px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-purple-900/10 transition-all hover:scale-[1.02] hover:bg-[#4752C4] active:scale-[0.98]"
                >
                    @svg ('fab-discord', 'h-5 w-5 transition-transform group-hover:rotate-12')
                    Continuar com Discord
                </a>

                <a
                    href="{{ route('oauth.redirect', ['tenant' => request()->getHost(), 'panel' => $panelId, 'provider' => 'github']) }}"
                    class="group flex items-center justify-center gap-3 rounded-xl bg-zinc-900 px-4 py-3.5 text-sm font-bold text-white shadow-lg ring-1 ring-zinc-800 transition-all hover:scale-[1.02] hover:bg-zinc-800 active:scale-[0.98]"
                >
                    @svg ('fab-github', 'h-5 w-5 transition-transform group-hover:scale-110')
                    Continuar com GitHub
                </a>

                <a
                    href="{{ route('oauth.redirect', ['tenant' => request()->getHost(), 'panel' => $panelId, 'provider' => 'twitch']) }}"
                    class="group flex items-center justify-center gap-3 rounded-xl bg-[#9146FF] px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-purple-900/10 transition-all hover:scale-[1.02] hover:bg-[#7B2FF0] active:scale-[0.98]"
                >
                    @svg ('fab-twitch', 'h-5 w-5 transition-transform group-hover:-rotate-12')
                    Continuar com Twitch
                </a>
            </div>

            {{-- Divider --}}
            <div class="relative my-10">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-zinc-800/60"></div>
                </div>
                <div class="relative flex justify-center text-[10px] font-black uppercase tracking-[0.2em]">
                    <span class="bg-zinc-950 px-6 text-zinc-400">ou e-mail</span>
                </div>
            </div>

            {{-- Filament email/password form --}}
            <div class="fi-login-form animate-in fade-in slide-in-from-bottom-6 duration-1000">
                <div class="[&_label]:text-white [&_span]:text-white [&_a]:text-purple-400">
                    {{ $this->form }}
                </div>

                <div class="mt-8">
                    @php
                        $action = $this->getAuthenticateFormAction();
                    @endphp
                    
                    <button
                        type="submit"
                        wire:click="authenticate"
                        wire:loading.attr="disabled"
                        class="w-full rounded-xl bg-purple-600 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-purple-900/20 transition-all hover:scale-[1.02] hover:bg-purple-500 active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed"
                    >
                        <span wire:loading.remove wire:target="authenticate">
                            {{ $action->getLabel() }}
                        </span>
                        <span wire:loading wire:target="authenticate" class="flex items-center justify-center gap-2">
                            <svg class="h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Entrando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
