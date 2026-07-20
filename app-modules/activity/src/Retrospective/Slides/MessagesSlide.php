<?php

declare(strict_types=1);

namespace He4rt\Activity\Retrospective\Slides;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * Panorama de mensagens: total no recorte, quantas renderam reação, quantas
 * foram fixadas, e o topo de quem mais conversou (nome resolvido em PHP só para
 * as N pessoas do ranking).
 */
final readonly class MessagesSlide implements Slide
{
    /**
     * @param  list<array{name: string, messages: int}>  $chatters
     */
    public function __construct(
        private int $total,
        private int $withReactions,
        private int $pinned,
        private array $chatters,
    ) {}

    public function kind(): string
    {
        return 'discord.messages';
    }

    /**
     * @return array{total: int, with_reactions: int, pinned: int, chatters: list<array{name: string, messages: int}>}
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'with_reactions' => $this->withReactions,
            'pinned' => $this->pinned,
            'chatters' => $this->chatters,
        ];
    }
}
