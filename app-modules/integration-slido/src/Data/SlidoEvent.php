<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Data;

use He4rt\IntegrationSlido\Exceptions\SlidoResponseInvalid;

final readonly class SlidoEvent
{
    public function __construct(
        public SlidoEventLocation $location,
        public string $name,
        public string $code,
        public bool $isActive,
        public string $participantUrl,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromPayload(SlidoEventLocation $location, array $payload): self
    {
        $name = $payload['name'] ?? null;
        $participantUrl = is_array($payload['url'] ?? null) ? ($payload['url']['app'] ?? null) : null;

        throw_unless(is_string($name) && is_string($participantUrl), SlidoResponseInvalid::missing('event', 'name, url.app'));

        return new self(
            location: $location,
            name: $name,
            code: is_scalar($payload['code'] ?? null) ? (string) $payload['code'] : '',
            isActive: ($payload['is_active'] ?? false) === true,
            participantUrl: $participantUrl,
        );
    }

    /**
     * @return array{hash: string, name: string, code: string, isActive: bool, participantUrl: string}
     */
    public function toArray(): array
    {
        return [
            'hash' => $this->location->hash,
            'name' => $this->name,
            'code' => $this->code,
            'isActive' => $this->isActive,
            'participantUrl' => $this->participantUrl,
        ];
    }
}
