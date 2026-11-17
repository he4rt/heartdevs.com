<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Users\Queries;

use App\Support\Query\TableQuery;
use He4rt\Identity\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class ListUsers
{
    /**
     * @param  array<array-key, mixed>  $parameters  filter[search], sort, page, per_page
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(array $parameters): LengthAwarePaginator
    {
        return QueryBuilder::for(User::query()->with('roles'), new Request($parameters))
            ->allowedFilters(AllowedFilter::callback('search', $this->search(...)))
            ->allowedSorts('name', 'email', 'created_at')
            ->defaultSort('name')
            ->orderBy('id')
            ->paginate(TableQuery::perPage($parameters), page: TableQuery::page($parameters));
    }

    /**
     * Commas split filter values into an array; a search term keeps them.
     *
     * @param  Builder<User>  $query
     */
    private function search(Builder $query, mixed $value): void
    {
        $term = TableQuery::term($value);

        $query->where(fn (Builder $query): Builder => $query
            ->whereLike('name', sprintf('%%%s%%', $term))
            ->orWhereLike('email', sprintf('%%%s%%', $term)));
    }
}
