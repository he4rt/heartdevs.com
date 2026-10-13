@props (['src' => null, 'name', 'size' => 44])
{{--
    Foto da pessoa ou, sem conta que a identifique, as iniciais no mesmo espaço.
    Nunca chuta `github.com/{nome}.png`: com um nome que não é login, a URL
    quebra ou mostra a foto de outra pessoa.
--}}
@if ($src)
    <img
        src="{{ $src }}"
        width="{{ $size }}"
        height="{{ $size }}"
        alt="{{ $name }}"
        {{ $attributes->merge(['style' => sprintf('width: %dpx; height: %dpx', $size, $size)]) }}
    />
@else
    <span
        role="img"
        aria-label="{{ $name }}"
        {{ $attributes->class(['retro-initials'])->merge(['style' => sprintf('width: %dpx; height: %dpx; font-size: %dpx', $size, $size, max(9, intdiv($size, 3)))]) }}
    >{{ str(str($name)->squish()->explode(' ')->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->implode(''))->upper()->toString() ?: '?' }}</span>
@endif
