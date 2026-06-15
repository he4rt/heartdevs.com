<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Actions;

use He4rt\Docs\Discovery\DTOs\RenderedMarkdown;
use He4rt\Docs\Discovery\Markdown\DiscoveryMarkdownConverter;
use Illuminate\Support\Str;

/**
 * Renders markdown to HTML and builds its table of contents. This is the
 * expensive step, run lazily per page (and cached by the registry).
 */
final readonly class RenderMarkdownAction
{
    public function execute(string $content): RenderedMarkdown
    {
        $html = new DiscoveryMarkdownConverter()->convert($content)->getContent();

        $toc = [];

        $html = (string) preg_replace_callback(
            '/<h([23])([^>]*)>(.*?)<\/h\1>/s',
            static function (array $matches) use (&$toc): string {
                $level = (int) $matches[1];
                $attributes = $matches[2];
                $inner = $matches[3];

                $text = mb_trim(strip_tags($inner));
                $id = Str::slug($text);

                if (!str_contains($attributes, 'id=')) {
                    $attributes .= ' id="'.$id.'"';
                }

                $toc[] = ['level' => $level, 'text' => $text, 'id' => $id];

                return sprintf('<h%s%s>%s</h%s>', $level, $attributes, $inner, $level);
            },
            $html,
        );

        return new RenderedMarkdown(html: $html, toc: $toc);
    }
}
