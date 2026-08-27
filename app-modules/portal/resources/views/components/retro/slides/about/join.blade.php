{{--
    Onde encontrar a comunidade e como entrar. Fecha a apresentação para quem
    chegou de fora — daqui em diante o deck só fala em números, e eles já vão
    fazer sentido.

    Os canais saem do config `he4rt.social_media`, a mesma fonte da página /redes:
    um link novo aparece aqui sem ninguém lembrar de vir editar o deck. Os canais
    que ESTE recorte mediu ganham selo, que é o que amarra a apresentação aos
    slides seguintes em vez de deixar uma lista de links solta.
--}}
@php
    $measured = collect($sources)->pluck('key')->all();
    $links = \He4rt\Portal\SocialLinks\SocialLinksPage::links();
    $discord = config()->string('he4rt.social_media.discord.url');
@endphp
<section class="slide" data-label="Onde entrar">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>Onde encontrar</span>
        <h2 class="sec" data-anim>A He4rt não cabe num lugar só</h2>
        <p class="sec-sub" data-anim>
            O Discord é a sala de estar e o GitHub é a bancada. O resto é por onde a comunidade
            aparece para quem ainda não chegou — sem processo seletivo, nível mínimo nem
            linguagem certa.
        </p>

        <div class="pgrid" data-anim style="margin-top: 26px">
            @foreach ($links as $link)
                <a class="card chan" href="{{ $link->url }}" target="_blank" rel="noopener">
                    <span class="chan-ic">
                        <x-filament::icon :icon="$link->icon" />
                    </span>

                    <span style="min-width: 0">
                        <span class="chan-name">{{ $link->label }}</span>
                        <span class="chan-host">
                            {{ \Illuminate\Support\Str::of($link->url)->after('//')->before('/') }}
                        </span>
                    </span>

                    @if (in_array($link->key, $measured, strict: true))
                        <span class="bdg neu" style="margin-inline-start: auto">medido aqui</span>
                    @endif
                </a>
            @endforeach
        </div>

        <div data-anim style="text-align: center">
            <a class="cta" href="{{ $discord }}" target="_blank" rel="noopener">
                Entrar na He4rt
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
        </div>
    </div>
</section>
