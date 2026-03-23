<?php

declare(strict_types=1);

namespace He4rt\Community\Feedback\DTOs;

use He4rt\Community\Feedback\Enums\ReviewTypeEnum;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use JsonSerializable;

final readonly class FeedbackReviewDTO implements JsonSerializable
{
    public function __construct(
        public string $feedbackId,
        public ReviewTypeEnum $reviewTypeEnum,
        public ExternalIdentity $adminProvider,
        public ?string $reason,
    ) {}

    public static function make(
        string $feedbackId,
        string $reviewType,
        ExternalIdentity $provider,
        ?string $reason
    ): self {
        return new self(
            feedbackId: $feedbackId,
            reviewTypeEnum: ReviewTypeEnum::from($reviewType),
            adminProvider: $provider,
            reason: $reason
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'feedback_id' => $this->feedbackId,
            'staff_id' => $this->adminProvider->model_id,
            'status' => $this->reviewTypeEnum->value,
            'reason' => $this->reason,
            'received_at' => now(),
        ];
    }
}
