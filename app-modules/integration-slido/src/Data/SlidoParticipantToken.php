<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Data;

use Carbon\CarbonImmutable;
use He4rt\IntegrationSlido\Exceptions\SlidoResponseInvalid;

final readonly class SlidoParticipantToken
{
    private const int REFRESH_MARGIN_SECONDS = 90;

    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public CarbonImmutable $expiresAt,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        $accessToken = $payload['access_token'] ?? null;
        $refreshToken = $payload['refresh_token'] ?? null;
        $expiresIn = $payload['expires_in'] ?? null;

        $hasValidShape = is_string($accessToken) && is_string($refreshToken) && is_int($expiresIn);

        throw_unless($hasValidShape, SlidoResponseInvalid::missing('token', 'access_token, refresh_token, expires_in'));

        return new self($accessToken, $refreshToken, CarbonImmutable::now()->addSeconds($expiresIn));
    }

    public function needsRefresh(): bool
    {
        return CarbonImmutable::now()->addSeconds(self::REFRESH_MARGIN_SECONDS)->greaterThanOrEqualTo($this->expiresAt);
    }

    public function secondsUntilRefresh(): int
    {
        $seconds = (int) CarbonImmutable::now()->diffInSeconds($this->expiresAt->subSeconds(self::REFRESH_MARGIN_SECONDS), absolute: false);

        return max($seconds, 0);
    }
}
