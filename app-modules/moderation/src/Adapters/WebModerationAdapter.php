<?php

declare(strict_types=1);

namespace He4rt\Moderation\Adapters;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Contracts\ModerationPlatformContract;
use He4rt\Moderation\DTOs\ExecutionResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Models\ModerationAction;
use Throwable;

final class WebModerationAdapter implements ModerationPlatformContract
{
    public function platform(): Platform
    {
        return Platform::Web;
    }

    public function ingest(array $rawPayload): ModerationContentDTO
    {
        return new ModerationContentDTO(
            contentId: $rawPayload['content_id'] ?? '',
            contentType: $rawPayload['content_type'] ?? 'post',
            sourcePlatform: Platform::Web,
            authorExternalId: $rawPayload['author_id'] ?? '',
            author: null,
            textContent: $rawPayload['text'] ?? '',
            mediaUrls: $rawPayload['media_urls'] ?? [],
            metadata: $rawPayload['metadata'] ?? [],
            snapshot: $rawPayload,
            tenantId: $rawPayload['tenant_id'] ?? null,
        );
    }

    public function execute(ModerationAction $action, User $target): ExecutionResultDTO
    {
        try {
            match ($action->action_type) {
                ActionType::Warn => $this->executeWarn(),
                ActionType::Suspend => $this->executeSuspend($target, $action->duration),
                ActionType::Ban => $this->executeBan($target),
                ActionType::ContentRemove => $this->executeContentRemove(),
                default => null,
            };

            return new ExecutionResultDTO(
                platform: Platform::Web,
                success: true,
                error: null,
                platformResponse: ['action' => $action->action_type->value],
            );
        } catch (Throwable $throwable) {
            return new ExecutionResultDTO(
                platform: Platform::Web,
                success: false,
                error: $throwable->getMessage(),
                platformResponse: [],
            );
        }
    }

    public function notify(User $user, string $message, array $context = []): void {}

    /** @return array<ActionType> */
    public function supports(): array
    {
        return [ActionType::Warn, ActionType::Suspend, ActionType::Ban, ActionType::ContentRemove];
    }

    public function resolveUser(string $externalId): ?User
    {
        return User::query()->find($externalId);
    }

    private function executeWarn(): void {}

    private function executeSuspend(User $target, ?string $duration): void
    {
        $until = match ($duration) {
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            '24h' => now()->addHours(24),
            default => now()->addDays(7),
        };

        $target->update(['suspended_until' => $until]);
    }

    private function executeBan(User $target): void
    {
        $target->update(['banned_at' => now()]);
    }

    private function executeContentRemove(): void {}
}
