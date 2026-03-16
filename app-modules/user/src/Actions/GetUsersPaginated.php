<?php

declare(strict_types=1);

namespace He4rt\User\Actions;

use App\Contracts\Paginator;
use He4rt\User\Contracts\UserRepository;

final readonly class GetUsersPaginated
{
    public function __construct(private UserRepository $repository) {}

    public function handle(): Paginator
    {
        return $this->repository
            ->paginated();
    }
}
