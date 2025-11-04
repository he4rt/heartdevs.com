<?php

declare(strict_types=1);

namespace He4rt\Character\Tests\Unit;

use He4rt\Provider\Entities\ProviderEntity;

trait ProviderProviderTrait
{
    public function validProviderPayload(array $fields = []): array
    {
        return [
            'id' => 'canhassi-id',
            'model_id' => 'user-id',
            'provider' => 'he4rt',
            'provider_id' => 'provider-id',
            'email' => 'canhas@gmail.com',
            ...$fields,
        ];
    }

    public function validProviderEntity(): ProviderEntity
    {
        return ProviderEntity::make($this->validProviderPayload());
    }
}
