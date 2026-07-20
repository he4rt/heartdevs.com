<?php

declare(strict_types=1);

namespace He4rt\Activity\Retrospective\Slides;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * Painel de voz: quantas pessoas passaram pelas calls, XP somado e os canais
 * mais movimentados. channel_name existe em voice_messages, então o ranking de
 * canal não precisa resolver nome no portal.
 */
final readonly class VoiceBoardSlide implements Slide
{
    /**
     * @param  list<array{name: string, events: int, xp: int}>  $channels
     */
    public function __construct(
        private int $participants,
        private int $xp,
        private array $channels,
    ) {}

    public function kind(): string
    {
        return 'discord.voice_board';
    }

    /**
     * @return array{participants: int, xp: int, channels: list<array{name: string, events: int, xp: int}>}
     */
    public function toArray(): array
    {
        return [
            'participants' => $this->participants,
            'xp' => $this->xp,
            'channels' => $this->channels,
        ];
    }
}
