<?php

declare(strict_types=1);

namespace He4rt\Community\Feedback\Actions;

use He4rt\Community\Feedback\DTOs\NewFeedbackDTO;
use He4rt\Community\Feedback\Models\Feedback;
use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class CreateFeedback
{
    public function __construct(
        private FindExternalIdentity $findExternalIdentity
    ) {}

    public function handle(array $payload): Feedback
    {
        $payload = $this->transformPeopleInvolvedIds($payload);
        $newFeedbackDTO = NewFeedbackDTO::make($payload);

        return Feedback::query()->create([
            ...$newFeedbackDTO->jsonSerialize(),
            'tenant_id' => request()->input('tenant_id'),
        ]);
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
