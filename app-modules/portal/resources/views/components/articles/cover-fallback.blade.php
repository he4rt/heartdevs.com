{{-- Cinco dos artigos do acervo não têm capa na API. O bloco abaixo ocupa exatamente
     a mesma proporção do cover real, para a grade não ficar dentada. --}}
<div
    {{ $attributes->class('flex items-center justify-center overflow-hidden') }}
    style="background: linear-gradient(to bottom right, color-mix(in srgb, var(--primary) 22%, transparent), color-mix(in srgb, var(--secondary) 22%, transparent))"
    aria-hidden="true"
>
    <span class="text-text-medium font-mono text-2xl tracking-[0.3em] opacity-70">&lt;/&gt;</span>
</div>
