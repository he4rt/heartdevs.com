<?php

declare(strict_types=1);

namespace He4rt\Community\Feedback\DTOs;

use JsonSerializable;

final readonly class NewFeedbackDTO implements JsonSerializable
{
    public function __construct(
        private string $senderId,
        private string $targetId,
        private string $type,
        private string $message
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(array $payload): self
    {
        return new self(
            $payload['sender_id'],
            $payload['target_id'],
            $payload['type'],
            $payload['message']
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'sender_id' => $this->senderId,
            'target_id' => $this->targetId,
            'type' => $this->type,
            'message' => $this->message,
        ];
    }
}
