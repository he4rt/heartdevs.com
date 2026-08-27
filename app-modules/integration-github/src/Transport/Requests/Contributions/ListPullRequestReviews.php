<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Transport\Requests\Contributions;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ListPullRequestReviews extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $repo,
        private readonly int $number,
        private readonly int $page = 1,
        private readonly int $perPage = 100,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/repos/'.$this->repo.'/pulls/'.$this->number.'/reviews';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultQuery(): array
    {
        return ['per_page' => $this->perPage, 'page' => $this->page];
    }
}
