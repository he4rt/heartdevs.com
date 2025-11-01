<?php

declare(strict_types=1);
use Heart\User\Domain\Entities\UserEntity;

dataset('validUserPayloads', fn() => [
    [[
        'id' => 123,
        'name' => 'Luis Alberto Suárez',
        'username' => 'brabo3k',
        'is_donator' => true,
    ]],
    [[
        'id' => 1,
        'name' => 'Diego Souza',
        'username' => 'brabo4k',
        'is_donator' => false,
    ]],
]);
test('can create entity', function (array $userPayload): void {
    $user = UserEntity::fromArray($userPayload);

    expect($user)->toBeInstanceOf(UserEntity::class);
})->with('validUserPayloads');
