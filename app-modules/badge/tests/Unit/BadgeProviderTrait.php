<?php

declare(strict_types=1);

namespace He4rt\Badge\Tests\Unit;

use He4rt\Badge\Entities\BadgeEntity;

trait BadgeProviderTrait
{
    public function validBadgePayload(array $fields = []): array
    {
        return [
            'id' => 12,
            'name' => 'canhassi',
            'description' => 'é o canhas, esqueça tudo!',
            'image_url' => 'vaicaralho.jpg',
            'redeem_code' => 'he4rtDevelopers',
            'active' => true,
            'tenant_id' => 1,
        ];
    }

    public function validBadgeEntity(): BadgeEntity
    {
        return BadgeEntity::make($this->validBadgePayload());
    }
}
