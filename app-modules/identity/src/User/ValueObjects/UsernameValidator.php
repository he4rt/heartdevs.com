<?php

declare(strict_types=1);

namespace He4rt\Identity\User\ValueObjects;

use He4rt\Identity\User\Exceptions\UsernameException;

final class UsernameValidator
{
    /**
     * @var list<string>
     */
    public const array RESERVED_USERNAMES = [
        'admin',
        'administrator',
        'administrador',
        'administradora',
        'system',
        'sistema',
        'root',
        'he4rt',
        'heart',
        'he4rtdevs',
        'mod',
        'moderator',
        'moderador',
        'moderadora',
        'staff',
        'equipe',
        'support',
        'suporte',
        'help',
        'ajuda',
        'official',
        'oficial',
        'api',
        'bot',
        'null',
        'nulo',
        'undefined',
        'indefinido',
        'anonymous',
        'anonimo',
        'everyone',
        'todos',
        'here',
        'aqui',
    ];

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

        if (in_array($normalized, self::RESERVED_USERNAMES, strict: true)) {
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
