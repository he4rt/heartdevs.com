<?php

declare(strict_types=1);

namespace He4rt\Character\Tests\Unit;

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

trait ProviderProviderTrait
{
    public function validProviderPayload(array $fields = []): array
    {
        return [
            'id' => 'canhassi-id',
            'model_id' => 'user-id',
            'provider' => 'discord',
            'provider_id' => 'provider-id',
            'email' => 'canhas@gmail.com',
            ...$fields,
        ];
    }

    public function validProviderEntity(): ExternalIdentity
    {
        $payload = $this->validProviderPayload();
        $model = new ExternalIdentity();
        $model->forceFill($payload);

        return $model;
    }
}
