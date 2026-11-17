<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\FeatureFlags\Queries;

use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\StoredFeatureFlag;
use App\Support\Query\TableQuery;
use He4rt\Identity\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class ListFeatureFlagUsers
{
    /**
     * @param  array<array-key, mixed>  $parameters  filter[search|enabled], sort, page, per_page
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(FeatureFlag $flag, array $parameters): LengthAwarePaginator
    {
        $enabling = StoredFeatureFlag::query()
            ->switchedOn($flag)
            ->whereRaw('feature_flags.scope = concat(cast(? as text), identity_users.id)', [(new User)->getMorphClass().'|']);

        $users = User::query()
            ->select('identity_users.*')
            ->addSelect(['feature_enabled' => $enabling->clone()->selectRaw('true')->limit(1)]);

        return QueryBuilder::for($users, new Request($parameters))
            ->allowedFilters(
                AllowedFilter::callback('search', $this->search(...)),
                AllowedFilter::callback('enabled', fn (Builder $query, mixed $value): Builder => TableQuery::term($value) === 'on'
                    ? $query->whereExists($enabling->toBase())
                    : $query->whereNotExists($enabling->toBase())),
            )
            ->allowedSorts('name', 'email')
            ->defaultSort('name')
            ->orderBy('identity_users.id')
            ->paginate(TableQuery::perPage($parameters), page: TableQuery::page($parameters));
    }

    /** @param  Builder<User>  $query */
    private function search(Builder $query, mixed $value): void
    {
        $term = TableQuery::term($value);

        $query->where(fn (Builder $query): Builder => $query
            ->whereLike('name', sprintf('%%%s%%', $term))
            ->orWhereLike('email', sprintf('%%%s%%', $term)));
    }
}
