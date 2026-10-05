<?php

declare(strict_types=1);

use He4rt\Identity\User\Exceptions\UsernameException;
use He4rt\Identity\User\ValueObjects\UsernameValidator;

test('normalizes username by trimming whitespace and converting to lowercase', function (): void {
    expect(UsernameValidator::normalizeAndValidate('  CoolDev_99  '))->toBe('cooldev_99');
});

test('validates correct username formats', function (string $validUsername): void {
    expect(UsernameValidator::validate($validUsername))->toBe($validUsername);
})->with([
    'simple' => 'he4rtian',
    'with dot' => 'dev.php',
    'with underscore' => 'heart_code',
    'with dash' => 'front-end',
    'min length 2' => 'ab',
    'alphanumeric' => 'user1234',
]);

test('throws exception for invalid username formats', function (string $invalidUsername): void {
    expect(fn () => UsernameValidator::validate($invalidUsername))
        ->toThrow(UsernameException::class);
})->with([
    'single char' => 'a',
    'too long' => str_repeat('x', 33),
    'starts with dot' => '.username',
    'ends with dot' => 'username.',
    'starts with dash' => '-username',
    'ends with dash' => 'username-',
    'starts with underscore' => '_username',
    'ends with underscore' => 'username_',
    'consecutive dots' => 'user..name',
    'consecutive dashes' => 'user--name',
    'consecutive underscores' => 'user__name',
    'consecutive mixed' => 'user.-name',
    'invalid chars space' => 'user name',
    'invalid chars at symbol' => 'user@name',
    'invalid chars exclamation' => 'user!name',
]);

test('rejects reserved system usernames in english and portuguese', function (string $reservedName): void {
    expect(fn () => UsernameValidator::validate($reservedName))
        ->toThrow(UsernameException::class, "O @{$reservedName} está reservado para o sistema e não pode ser utilizado.");
})->with([
    // English
    'admin',
    'administrator',
    'system',
    'root',
    'he4rt',
    'heart',
    'he4rtdevs',
    'mod',
    'moderator',
    'staff',
    'support',
    'help',
    'official',
    'api',
    'bot',
    'null',
    'undefined',
    'anonymous',
    'everyone',
    'here',
    // Portuguese
    'administrador',
    'administradora',
    'sistema',
    'moderador',
    'moderadora',
    'equipe',
    'suporte',
    'ajuda',
    'oficial',
    'nulo',
    'indefinido',
    'anonimo',
    'todos',
    'aqui',
]);

test('rejects reserved usernames case-insensitively', function (string $reservedUpper, string $normalizedExpected): void {
    expect(fn () => UsernameValidator::validate($reservedUpper))
        ->toThrow(UsernameException::class, "O @{$normalizedExpected} está reservado para o sistema e não pode ser utilizado.");
})->with([
    ['ADMIN', 'admin'],
    ['SISTEMA', 'sistema'],
    ['Suporte', 'suporte'],
    ['EQUIPE', 'equipe'],
    ['Moderador', 'moderador'],
    ['TODOS', 'todos'],
    ['Aqui', 'aqui'],
]);
