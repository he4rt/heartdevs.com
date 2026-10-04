<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Data;

use He4rt\Streaming\Enums\VoiceLayout;

final readonly class VoiceSettings implements SceneSettings
{
    public function __construct(
        public VoiceLayout $layout = VoiceLayout::Column,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $layout = is_string($payload['layout'] ?? null) ? VoiceLayout::tryFrom($payload['layout']) : null;

        return new self($layout ?? VoiceLayout::Column);
    }

    public function toArray(): array
    {
        return [
            'layout' => $this->layout->value,
        ];
    }
}
