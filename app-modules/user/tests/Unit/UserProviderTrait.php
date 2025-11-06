<?php

declare(strict_types=1);

namespace He4rt\User\Tests\Unit;

use He4rt\User\Entities\UserEntity;

trait UserProviderTrait
{
    public function validUserPayload(array $fields = []): array
    {
        return [
            'id' => '12',
            'username' => 'canhassi',
            'isDonator' => false,
            ...$fields,
        ];
    }

    public function validUserEntity(): UserEntity
    {
        return UserEntity::make($this->validUserPayload());
    }
}
