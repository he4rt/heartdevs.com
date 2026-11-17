<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Queries;

use App\Support\Query\TableQuery;
use He4rt\Identity\Teams\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class ListTeams
{
    /**
     * @param  array<array-key, mixed>  $parameters  filter[search|status|trashed], sort, page, per_page
     * @return LengthAwarePaginator<int, Team>
     */
    public function handle(array $parameters): LengthAwarePaginator
    {
        return QueryBuilder::for(Team::query()->with('owner')->withCount('members'), new Request($parameters))
            ->allowedFilters(
                AllowedFilter::callback('search', $this->search(...)),
                AllowedFilter::exact('status'),
                AllowedFilter::trashed(),
            )
            ->allowedSorts('name', 'members_count', 'created_at')
            ->defaultSort('name')
            ->orderBy('id')
            ->paginate(TableQuery::perPage($parameters), page: TableQuery::page($parameters));
    }

    /** @param  Builder<Team>  $query */
    private function search(Builder $query, mixed $value): void
    {
        $term = TableQuery::term($value);

        $query->where(fn (Builder $query): Builder => $query
            ->whereLike('name', sprintf('%%%s%%', $term))
            ->orWhereLike('slug', sprintf('%%%s%%', $term)));
    }
}
