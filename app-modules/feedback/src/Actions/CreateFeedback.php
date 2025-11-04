<?php

declare(strict_types=1);

namespace He4rt\Feedback\Actions;

use He4rt\Feedback\Contracts\FeedbackRepository;
use He4rt\Feedback\DTO\NewFeedbackDTO;
use He4rt\Feedback\Entities\FeedbackEntity;
use He4rt\Provider\Actions\FindProvider;
use He4rt\Provider\Enums\ProviderEnum;

final readonly class CreateFeedback
{
    public function __construct(
        private FeedbackRepository $feedbackRepository,
        private FindProvider $findProvider
    ) {}

    public function handle(array $payload): FeedbackEntity
    {
        $payload = $this->transformPeopleInvolvedIds($payload);
        $newFeedbackDTO = NewFeedbackDTO::make($payload);

        return $this->feedbackRepository->create($newFeedbackDTO);
    }

    private function transformPeopleInvolvedIds(array $payload): array
    {
        $senderUserEntity = $this->findProvider->handle(ProviderEnum::Discord->value, $payload['sender_id']);
        $targetUserEntity = $this->findProvider->handle(ProviderEnum::Discord->value, $payload['target_id']);

        $payload['sender_id'] = $senderUserEntity->modelId;
        $payload['target_id'] = $targetUserEntity->modelId;

        return $payload;
    }
}
