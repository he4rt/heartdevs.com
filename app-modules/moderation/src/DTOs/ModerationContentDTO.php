<?php

declare(strict_types=1);

namespace He4rt\Moderation\DTOs;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Models\ModerationCase;
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

    public static function fromCase(ModerationCase $case): self
    {
        return new self(
            contentId: $case->content_id,
            contentType: $case->content_type,
            sourcePlatform: $case->source_platform ?? Platform::Web,
            authorExternalId: '',
            author: $case->author,
            textContent: $case->content_snapshot['text'] ?? '',
            mediaUrls: $case->content_snapshot['media_urls'] ?? [],
            metadata: $case->content_snapshot['metadata'] ?? [],
            snapshot: $case->content_snapshot ?? [],
            tenantId: $case->tenant_id !== null ? (string) $case->tenant_id : null,
        );
    }

    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public static function fromPlatform(Platform $platform, array $rawPayload): self
    {
        return new self(
            contentId: $rawPayload['content_id'] ?? '',
            contentType: $rawPayload['content_type'] ?? 'message',
            sourcePlatform: $platform,
            authorExternalId: $rawPayload['author_external_id'] ?? '',
            author: null,
            textContent: $rawPayload['text'] ?? '',
            mediaUrls: $rawPayload['media_urls'] ?? [],
            metadata: $rawPayload['metadata'] ?? [],
            snapshot: $rawPayload,
            tenantId: $rawPayload['tenant_id'] ?? null,
        );
    }
}
