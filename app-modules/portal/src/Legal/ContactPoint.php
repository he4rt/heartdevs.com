<?php

declare(strict_types=1);

namespace He4rt\Portal\Legal;

use InvalidArgumentException;

final readonly class ContactPoint
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        $contacts = [];

        foreach (config()->array('he4rt.privacy.contacts') as $contact) {
            throw_unless(
                is_array($contact) && is_string($contact['name'] ?? null) && is_string($contact['email'] ?? null),
                InvalidArgumentException::class,
                "Configuração he4rt.privacy.contacts inválida: cada contato precisa de 'name' e 'email'.",
            );

            $contacts[] = new self($contact['name'], $contact['email']);
        }

        return $contacts;
    }
}
