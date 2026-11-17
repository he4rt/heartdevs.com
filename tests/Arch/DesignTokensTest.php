<?php

declare(strict_types=1);

/*
 * Gate for DESIGN.md: the colour tokens in its front matter are the values of resources/css/app.css.
 * A token without a prefix is a :root (light) value; a token with the dark- prefix is the .dark value of the same slot.
 */

/**
 * @return array<string, string> slot => value
 */
function cssBlock(string $css, string $selector): array
{
    preg_match('/^'.preg_quote($selector, '/').' \{\n(.*?)\n\}/ms', $css, $block);
    preg_match_all('/--([\w-]+):\s*([^;]+);/', $block[1] ?? '', $declarations, PREG_SET_ORDER);

    return collect($declarations)->mapWithKeys(fn (array $declaration): array => [$declaration[1] => $declaration[2]])->all();
}

/**
 * @return array<string, array{string, string, string}>
 */
function designColorTokens(): array
{
    $root = dirname(__DIR__, 2);
    $design = (string) file_get_contents($root.'/DESIGN.md');
    $css = (string) file_get_contents($root.'/resources/css/app.css');

    preg_match('/^colors:\n(.*?)^\S/ms', $design, $colors);
    preg_match_all('/^\s+([\w-]+):\s*[\'"]([^\'"]+)[\'"]\s*$/m', $colors[1] ?? '', $tokens, PREG_SET_ORDER);

    $light = cssBlock($css, ':root');
    $dark = cssBlock($css, '.dark');

    return collect($tokens)
        ->mapWithKeys(function (array $token) use ($light, $dark): array {
            $isDark = str_starts_with($token[1], 'dark-');
            $slot = $isDark ? mb_substr($token[1], 5) : $token[1];
            $cssValue = ($isDark ? $dark : $light)[$slot] ?? '(missing in app.css)';

            return [$token[1] => [$token[1], $token[2], $cssValue]];
        })
        ->all();
}

test('DESIGN.md defines every colour slot of app.css for both themes', function (): void {
    expect(designColorTokens())->toHaveCount(66);
});

test('a DESIGN.md colour token has the same value in app.css', function (string $token, string $designValue, string $cssValue): void {
    expect($cssValue)->toBe($designValue, "{$token} differs between DESIGN.md and app.css");
})->with(fn (): array => designColorTokens());
