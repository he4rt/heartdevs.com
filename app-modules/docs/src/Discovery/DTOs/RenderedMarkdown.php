<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\DTOs;

/**
 * The rendered HTML of a document plus its table of contents.
 */
final readonly class RenderedMarkdown
{
    /**
     * @param  list<array{level: int, text: string, id: string}>  $toc
     */
    public function __construct(
        public string $html,
        public array $toc,
    ) {}
}
