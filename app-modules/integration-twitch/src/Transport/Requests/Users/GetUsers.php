<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Transport\Requests\Users;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetUsers extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly ?string $login = null,
        private readonly ?string $id = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/users';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        $query = [];

        if ($this->login !== null) {
            $query['login'] = $this->login;
        }

        if ($this->id !== null) {
            $query['id'] = $this->id;
        }

        return $query;
    }
}
