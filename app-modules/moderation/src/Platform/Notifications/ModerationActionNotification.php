<?php

declare(strict_types=1);

namespace He4rt\Moderation\Platform\Notifications;

use He4rt\Moderation\Enforcement\ModerationAction;
use Illuminate\Notifications\Notification;

final class ModerationActionNotification extends Notification
{
    public function __construct(
        private readonly ModerationAction $action,
        private readonly string $message,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'action_id' => $this->action->id,
            'action_type' => $this->action->action_type->value,
            'case_id' => $this->action->case_id,
            'duration' => $this->action->duration,
            'reason' => $this->action->reason,
            'message' => $this->message,
        ];
    }
}
