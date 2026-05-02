<?php

declare(strict_types=1);

namespace He4rt\Moderation\DTOs;

use He4rt\Moderation\Enums\Platform;

final readonly class ExecutionResultDTO
{
    /**
     * @param  array<string, mixed>  $platformResponse
     */
    public function __construct(
        public Platform $platform,
        public bool $success,
        public ?string $error,
        public array $platformResponse,
    ) {}

    /**
     * @param  array<string, mixed>  $response
     */
    public static function success(Platform $platform, array $response = []): self
    {
        return new self(
            platform: $platform,
            success: true,
            error: null,
            platformResponse: $response,
        );
    }

    public static function failure(Platform $platform, string $error): self
    {
        return new self(
            platform: $platform,
            success: false,
            error: $error,
            platformResponse: [],
        );
    }
}
