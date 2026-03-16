<?php

declare(strict_types=1);

namespace He4rt\Meeting\Actions;

use App\Contracts\Paginator;
use He4rt\Provider\Enums\ProviderEnum;

final readonly class PaginateMeetings
{
    public function __construct(private PaginateMeetingsAction $paginateMeetingsAction) {}

    public function handle(string $provider): Paginator
    {
        ProviderEnum::from($provider);

        return $this->paginateMeetingsAction->handle();
    }
}
