<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\DTOs;

use Illuminate\Support\Facades\Crypt;
use JsonSerializable;
use Stringable;

class OAuthStateDTO implements JsonSerializable, Stringable
{
    public function __construct(
        public string $panel,
        public ?string $tenant = null,
    ) {}

    public function __toString(): string
    {
        return Crypt::encryptString(json_encode($this));
    }

    public static function fromHashedString(string $state): self
    {
        return new self(...json_decode(Crypt::decryptString($state), true));
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {

        return [
            'panel' => $this->panel,
            'tenant' => $this->tenant ?? null,
        ];
    }
}
