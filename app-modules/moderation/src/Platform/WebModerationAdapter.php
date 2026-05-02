<?php

declare(strict_types=1);

namespace He4rt\Moderation\Platform;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\DTOs\ExecutionResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use Throwable;

final class WebModerationAdapter implements ModerationPlatformContract
{
    public function platform(): Platform
    {
        return Platform::Web;
    }

    public function ingest(array $rawPayload): ModerationContentDTO
    {
        return ModerationContentDTO::fromPlatform(Platform::Web, $rawPayload);
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

            return ExecutionResultDTO::success(Platform::Web, ['action' => $action->action_type->value]);
        } catch (Throwable $throwable) {
            return ExecutionResultDTO::failure(Platform::Web, $throwable->getMessage());
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
