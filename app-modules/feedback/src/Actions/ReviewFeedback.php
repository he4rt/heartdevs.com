<?php

declare(strict_types=1);

namespace He4rt\Feedback\Actions;

use He4rt\Feedback\DTO\FeedbackReviewDTO;
use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class ReviewFeedback
{
    public function __construct(
        private PersistFeedbackReview $persistReview,
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

        $this->persistReview->handle($reviewDTO);
    }
}
