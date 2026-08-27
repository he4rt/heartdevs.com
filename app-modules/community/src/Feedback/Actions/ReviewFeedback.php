<?php

declare(strict_types=1);

namespace He4rt\Community\Feedback\Actions;

use He4rt\Community\Feedback\DTOs\FeedbackReviewDTO;
use He4rt\Community\Feedback\Models\Review;
use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class ReviewFeedback
{
    public function __construct(
        private FindExternalIdentity $findExternalIdentity,
    ) {}

    public function handle(
        string $feedbackId,
        string $reviewType,
        string $providerAdminId,
        ?string $reason = null
    ): void {
        $externalIdentity = $this->findExternalIdentity->handle(IdentityProvider::Discord->value, $providerAdminId);
        $reviewDTO = FeedbackReviewDTO::make($feedbackId, $reviewType, $externalIdentity, $reason);

        Review::query()->create([
            ...$reviewDTO->jsonSerialize(),
        ]);
    }
}
