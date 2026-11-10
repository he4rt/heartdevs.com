<?php

declare(strict_types=1);

namespace He4rt\Identity\User\ValueObjects;

use He4rt\Identity\User\Exceptions\UsernameException;

final class UsernameValidator
{
    /**
     * @var list<string>|null
     */
    private static ?array $reservedUsernames = null;

    /**
     * @return list<string>
     */
    public static function reservedUsernames(): array
    {
        if (self::$reservedUsernames !== null) {
            return self::$reservedUsernames;
        }

        $path = __DIR__.'/../../../resources/usernames/invalid-usernames.txt';

        if (!file_exists($path)) {
            return self::$reservedUsernames = [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return self::$reservedUsernames = [];
        }

        $reserved = [];

        foreach ($lines as $line) {
            $trimmed = mb_strtolower(mb_trim($line));

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $reserved[] = $trimmed;
        }

        return self::$reservedUsernames = array_values(array_unique($reserved));
    }

    public static function isReserved(string $username): bool
    {
        $normalized = mb_strtolower(mb_trim($username));

        return in_array($normalized, self::reservedUsernames(), strict: true);
    }

    /**
     * @throws UsernameException
     */
    public static function normalizeAndValidate(string $username): string
    {
        $normalized = mb_strtolower(mb_trim($username));
        $length = mb_strlen($normalized);

        if ($length < 2 || $length > 32) {
            throw UsernameException::invalidFormat(
                'o tamanho deve ter entre 2 e 32 caracteres',
                'panel-app::profile.validation.username_reason_length',
            );
        }

        if (!preg_match('/^[a-z0-9._-]+$/', $normalized)) {
            throw UsernameException::invalidFormat(
                'apenas letras, números, sublinhado (_), hífen (-) e ponto (.) são permitidos',
                'panel-app::profile.validation.username_reason_characters',
            );
        }

        if (!preg_match('/^[a-z0-9]/', $normalized) || !preg_match('/[a-z0-9]$/', $normalized)) {
            throw UsernameException::invalidFormat(
                'não pode começar ou terminar com caracteres especiais',
                'panel-app::profile.validation.username_reason_edges',
            );
        }

        if (preg_match('/[._-]{2,}/', $normalized)) {
            throw UsernameException::invalidFormat(
                'não pode conter caracteres especiais consecutivos',
                'panel-app::profile.validation.username_reason_consecutive',
            );
        }

        if (self::isReserved($normalized)) {
            throw UsernameException::reservedUsername($normalized);
        }

        return $normalized;
    }

    /**
     * @throws UsernameException
     */
    public static function validate(string $username): string
    {
        return self::normalizeAndValidate($username);
    }
}
