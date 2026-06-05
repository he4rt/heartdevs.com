<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Transport\Requests\Contributions;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetPullRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $repo,
        private readonly int $number,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/repos/'.$this->repo.'/pulls/'.$this->number;
    }
}
