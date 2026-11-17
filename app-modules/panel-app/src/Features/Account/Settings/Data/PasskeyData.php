<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Data;

use DateTimeInterface;
use Laravel\Passkeys\Passkey;
use Spatie\LaravelData\Data;

final class PasskeyData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $authenticator,
        public string $createdAt,
        public ?string $lastUsedAt,
    ) {}

    public static function fromModel(Passkey $passkey): self
    {
        $authenticator = $passkey->getAttribute('authenticator');
        $lastUsedAt = $passkey->getAttribute('last_used_at');

        return new self(
            id: (int) $passkey->getKey(),
            name: (string) $passkey->getAttribute('name'),
            authenticator: is_string($authenticator) ? $authenticator : null,
            createdAt: $passkey->created_at?->toIso8601String() ?? '',
            lastUsedAt: $lastUsedAt instanceof DateTimeInterface ? $lastUsedAt->format(DATE_ATOM) : null,
        );
    }
}
