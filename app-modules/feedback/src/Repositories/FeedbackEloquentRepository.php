<?php

declare(strict_types=1);

namespace He4rt\Feedback\Repositories;

use He4rt\Feedback\Contracts\FeedbackRepository;
use He4rt\Feedback\DTO\FeedbackReviewDTO;
use He4rt\Feedback\DTO\NewFeedbackDTO;
use He4rt\Feedback\Entities\FeedbackEntity;
use He4rt\Feedback\Exceptions\FeedbackException;
use He4rt\Feedback\Models\Feedback;

final readonly class FeedbackEloquentRepository implements FeedbackRepository
{
    public function __construct(private Feedback $model) {}

    public function findById(string $id): FeedbackEntity
    {
        $model = $this->model
            ->newQuery()
            ->find($id);

        throw_unless($model, FeedbackException::idNotFound((int) $id));

        return FeedbackEntity::make($model->toArray());
    }

    public function create(NewFeedbackDTO $dto): FeedbackEntity
    {
        $model = $this->model->newQuery()->create([
            ...$dto->jsonSerialize(),
            'tenant_id' => request()->input('tenant_id'),
        ]);

        return FeedbackEntity::make($model->toArray());
    }

    public function reviewFeedback(FeedbackReviewDTO $dto): void
    {
        $this->model->newQuery()
            ->find($dto->feedbackId)
            ->review()
            ->create([
                ...$dto->jsonSerialize(),
                'tenant_id' => request()->input('tenant_id'),
                'received_at' => now(),
            ]);
    }
}
