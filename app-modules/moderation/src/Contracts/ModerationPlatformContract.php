<?php

declare(strict_types=1);

namespace He4rt\Moderation\Contracts;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\DTOs\ExecutionResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Models\ModerationAction;

interface ModerationPlatformContract
{
    public function platform(): Platform;

    public function ingest(array $rawPayload): ModerationContentDTO;

    public function execute(ModerationAction $action, User $target): ExecutionResultDTO;

    public function notify(User $user, string $message, array $context = []): void;

    /** @return array<ActionType> */
    public function supports(): array;

    public function resolveUser(string $externalId): ?User;
}
