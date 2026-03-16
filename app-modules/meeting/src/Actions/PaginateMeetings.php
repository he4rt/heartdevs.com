<?php

declare(strict_types=1);

namespace He4rt\Meeting\Actions;

use App\Contracts\Paginator;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class PaginateMeetings
{
    public function __construct(private PaginateMeetingsAction $paginateMeetingsAction) {}

    public function handle(string $provider): Paginator
    {
        IdentityProvider::from($provider);

        return $this->paginateMeetingsAction->handle();
    }
}
