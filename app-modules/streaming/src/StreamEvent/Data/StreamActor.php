<?php

declare(strict_types=1);

namespace He4rt\Streaming\StreamEvent\Data;

final readonly class StreamActor
{
    public function __construct(
        public string $platformId,
        public string $login,
        public string $displayName,
    ) {}

    /**
     * @return array{login: string, displayName: string}
     */
    public function toBroadcast(): array
    {
        return [
            'login' => $this->login,
            'displayName' => $this->displayName,
        ];
    }
}
