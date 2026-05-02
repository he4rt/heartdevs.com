<?php

declare(strict_types=1);

namespace He4rt\Moderation\DTOs;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Enums\Platform;

final readonly class ModerationContentDTO
{
    /**
     * @param  array<string>  $mediaUrls
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public string $contentId,
        public string $contentType,
        public Platform $sourcePlatform,
        public string $authorExternalId,
        public ?User $author,
        public string $textContent,
        public array $mediaUrls,
        public array $metadata,
        public array $snapshot,
        public ?string $tenantId,
    ) {}
}
