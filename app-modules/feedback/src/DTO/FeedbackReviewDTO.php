<?php

declare(strict_types=1);

namespace He4rt\Feedback\DTO;

use He4rt\Feedback\Enum\ReviewTypeEnum;
use He4rt\Provider\Entities\ProviderEntity;
use JsonSerializable;

final readonly class FeedbackReviewDTO implements JsonSerializable
{
    public function __construct(
        public string $feedbackId,
        public ReviewTypeEnum $reviewTypeEnum,
        public ProviderEntity $adminProviderEntity,
        public ?string $reason,
    ) {}

    public static function make(
        string $feedbackId,
        string $reviewType,
        ProviderEntity $providerEntity,
        ?string $reason
    ): self {
        return new self(
            feedbackId: $feedbackId,
            reviewTypeEnum: ReviewTypeEnum::from($reviewType),
            adminProviderEntity: $providerEntity,
            reason: $reason
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'feedback_id' => $this->feedbackId,
            'staff_id' => $this->adminProviderEntity->modelId,
            'status' => $this->reviewTypeEnum->value,
            'reason' => $this->reason,
            'received_at' => $this->reason,
        ];
    }
}
