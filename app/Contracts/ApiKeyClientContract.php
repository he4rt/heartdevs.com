<?php

declare(strict_types=1);

namespace App\Contracts;

use He4rt\Identity\ExternalIdentity\DTOs\ApiKeyUserDTO;
use He4rt\Identity\ExternalIdentity\Exceptions\InvalidApiKeyException;

interface ApiKeyClientContract
{
    /**
     * Valida a chave contra o provider e devolve o perfil autenticado.
     *
     * @throws InvalidApiKeyException quando o provider rejeita a chave
     */
    public function getAuthenticatedUser(string $apiKey): ApiKeyUserDTO;
}
