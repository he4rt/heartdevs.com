<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Transport\Requests\Contributions;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ListPullRequests extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $repo,
        private readonly int $page = 1,
        private readonly int $perPage = 100,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/repos/'.$this->repo.'/pulls';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultQuery(): array
    {
        return [
            'state' => 'all',
            'sort' => 'created',
            'direction' => 'asc',
            'per_page' => $this->perPage,
            'page' => $this->page,
        ];
    }
}
