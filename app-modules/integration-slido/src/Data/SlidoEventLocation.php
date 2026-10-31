<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Data;

final readonly class SlidoEventLocation
{
    public function __construct(
        public string $hash,
        public string $uuid,
        public string $cluster,
    ) {}

    public function callbackUrl(): string
    {
        return sprintf('%s/event/%s/oauth-callback', mb_rtrim(config()->string('integration-slido.wall_url'), '/'), $this->hash);
    }

    public function streamTarget(): string
    {
        return sprintf('slido/events/%s/', $this->uuid);
    }
}
