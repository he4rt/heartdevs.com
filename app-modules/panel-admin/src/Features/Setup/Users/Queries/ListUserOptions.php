<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Users\Queries;

use He4rt\Identity\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The options of the async user pickers.
 */
final readonly class ListUserOptions
{
    private const int LIMIT = 20;

    /** @return Collection<int, User> */
    public function handle(string $search, ?string $excludedTeamId): Collection
    {
        return User::query()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query): Builder => $query
                ->whereLike('name', sprintf('%%%s%%', $search))
                ->orWhereLike('email', sprintf('%%%s%%', $search))))
            ->when($excludedTeamId !== null, fn (Builder $query) => $query->whereDoesntHave(
                'tenants',
                fn (Builder $query): Builder => $query->whereKey($excludedTeamId),
            ))
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get();
    }
}
