<?php

declare(strict_types=1);

namespace He4rt\Feedback\Actions;

use He4rt\Feedback\Contracts\FeedbackRepository;
use He4rt\Feedback\DTO\NewFeedbackDTO;
use He4rt\Feedback\Entities\FeedbackEntity;
use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class CreateFeedback
{
    public function __construct(
        private FeedbackRepository $feedbackRepository,
        private FindExternalIdentity $findExternalIdentity
    ) {}

    public function handle(array $payload): FeedbackEntity
    {
        $payload = $this->transformPeopleInvolvedIds($payload);
        $newFeedbackDTO = NewFeedbackDTO::make($payload);

        return $this->feedbackRepository->create($newFeedbackDTO);
    }

    private function transformPeopleInvolvedIds(array $payload): array
    {
        $senderIdentity = $this->findExternalIdentity->handle(IdentityProvider::Discord->value, $payload['sender_id']);
        $targetIdentity = $this->findExternalIdentity->handle(IdentityProvider::Discord->value, $payload['target_id']);

        $payload['sender_id'] = $senderIdentity->model_id;
        $payload['target_id'] = $targetIdentity->model_id;

        return $payload;
    }
}
