<?php

declare(strict_types=1);

namespace He4rt\Moderation\Platform;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\DTOs\ExecutionResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Platform\Notifications\ModerationActionNotification;
use Throwable;

final class WebModerationAdapter implements ModerationPlatformContract
{
    public static function make(): self
    {
        return new self();
    }

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
                ActionType::Warn => $this->executeWarn($target, $action),
                ActionType::Suspend => $this->executeSuspend($target, $action),
                ActionType::Ban => $this->executeBan($target, $action),
                ActionType::ContentRemove => $this->executeContentRemove($target, $action),
                default => null,
            };

            return ExecutionResultDTO::success(Platform::Web, ['action' => $action->action_type->value]);
        } catch (Throwable $throwable) {
            return ExecutionResultDTO::failure(Platform::Web, $throwable->getMessage());
        }
    }

    public function notify(User $user, string $message, array $context = []): void
    {
        $action = $context['action'] ?? null;

        if ($action instanceof ModerationAction) {
            $user->notify(new ModerationActionNotification($action, $message));

            return;
        }

        $user->notify(new ModerationActionNotification(
            $action ?? new ModerationAction(),
            $message,
        ));
    }

    /** @return array<ActionType> */
    public function supports(): array
    {
        return [ActionType::Warn, ActionType::Suspend, ActionType::Ban, ActionType::ContentRemove];
    }

    public function resolveUser(string $externalId): ?User
    {
        return User::query()->find($externalId);
    }

    private function executeWarn(User $target, ModerationAction $action): void
    {
        $target->notify(new ModerationActionNotification(
            $action,
            __('moderation::notifications.warn', ['reason' => $action->reason]),
        ));
    }

    private function executeSuspend(User $target, ModerationAction $action): void
    {
        $until = match ($action->duration) {
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            '24h' => now()->addHours(24),
            default => now()->addDays(7),
        };

        $target->update(['suspended_until' => $until]);

        $target->notify(new ModerationActionNotification(
            $action,
            __('moderation::notifications.suspend', ['until' => $until->toDateTimeString(), 'reason' => $action->reason]),
        ));
    }

    private function executeBan(User $target, ModerationAction $action): void
    {
        $target->update(['banned_at' => now()]);

        $target->notify(new ModerationActionNotification(
            $action,
            __('moderation::notifications.ban', ['duration' => $action->duration, 'reason' => $action->reason]),
        ));
    }

    private function executeContentRemove(User $target, ModerationAction $action): void
    {
        $target->notify(new ModerationActionNotification(
            $action,
            __('moderation::notifications.content_remove', ['reason' => $action->reason]),
        ));
    }
}
