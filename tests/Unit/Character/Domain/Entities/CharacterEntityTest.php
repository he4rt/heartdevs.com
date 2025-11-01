<?php

declare(strict_types=1);
use Heart\Character\Domain\Entities\CharacterEntity;

dataset('characterProvider', fn() => [
    [1, 1, 1, 548, '2023-01-14 00:26:25', 4],
    [1, 1, 1, 89, '2023-01-14 00:26:25', 1],
    [1, 1, 1, 287, '2023-01-14 00:26:25', 3],
]);
dataset('makeCharacterProvider', fn() => [
    [['id' => 1, 'user_id' => 1, 'reputation' => 1, 'experience' => 548, 'daily_bonus_claimed_at' => '2023-01-14 00:26:25'], 4],
    [['id' => 1, 'user_id' => 1, 'reputation' => 1, 'experience' => 98, 'daily_bonus_claimed_at' => '2023-01-14 00:26:25'], 2],
]);
test('instance character entity test', function (int $id, int $userId, int $reputation, int $experience, string $claimedAt, int $expectedLevel): void {
    $characterEntity = new CharacterEntity($id, $reputation, $userId, $experience, $claimedAt);

    self::assertEquals($expectedLevel, $characterEntity->getLevel());
    self::assertInstanceOf(CharacterEntity::class, $characterEntity);
})->with('characterProvider');
test('make character', function (array $payload, int $expectedLevel): void {
    $characterEntity = CharacterEntity::make($payload);

    self::assertEquals($expectedLevel, $characterEntity->getLevel());
    self::assertInstanceOf(CharacterEntity::class, $characterEntity);
})->with('makeCharacterProvider');
