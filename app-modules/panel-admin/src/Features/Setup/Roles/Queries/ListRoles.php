<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Roles\Queries;

use App\Support\Query\TableQuery;
use He4rt\Identity\Permissions\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class ListRoles
{
    /**
     * @param  array<array-key, mixed>  $parameters  filter[search], sort, page, per_page
     * @return LengthAwarePaginator<int, Role>
     */
    public function handle(array $parameters): LengthAwarePaginator
    {
        return QueryBuilder::for(Role::query()->withCount(['permissions', 'users']), new Request($parameters))
            ->allowedFilters(AllowedFilter::callback('search', $this->search(...)))
            ->allowedSorts('name', 'permissions_count', 'updated_at')
            ->defaultSort('name')
            ->orderBy('id')
            ->paginate(TableQuery::perPage($parameters), page: TableQuery::page($parameters));
    }

    /** @param  Builder<Role>  $query */
    private function search(Builder $query, mixed $value): void
    {
        $term = TableQuery::term($value);

        $query->whereLike('name', sprintf('%%%s%%', $term));
    }
}
