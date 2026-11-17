<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Members\Queries;

use App\Support\Query\TableQuery;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

final class ListTeamMembers
{
    private const string PIVOT = 'identity_team_user';

    /**
     * @param  array<array-key, mixed>  $parameters  filter[search], sort, page, per_page
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(Team $team, array $parameters): LengthAwarePaginator
    {
        $members = User::query()
            ->select('identity_users.*')
            ->selectRaw(sprintf('%s.created_at as joined_at', self::PIVOT))
            ->join(self::PIVOT, sprintf('%s.user_id', self::PIVOT), '=', 'identity_users.id')
            ->where(sprintf('%s.team_id', self::PIVOT), $team->getKey());

        return QueryBuilder::for($members, new Request($parameters))
            ->allowedFilters(AllowedFilter::callback('search', $this->search(...)))
            ->allowedSorts(
                AllowedSort::field('name', 'identity_users.name'),
                AllowedSort::field('email', 'identity_users.email'),
                AllowedSort::field('joined_at', sprintf('%s.created_at', self::PIVOT)),
            )
            ->defaultSort('name')
            ->orderBy('identity_users.id')
            ->paginate(TableQuery::perPage($parameters), page: TableQuery::page($parameters));
    }

    /** @param  Builder<User>  $query */
    private function search(Builder $query, mixed $value): void
    {
        $term = TableQuery::term($value);

        $query->where(fn (Builder $query): Builder => $query
            ->whereLike('identity_users.name', sprintf('%%%s%%', $term))
            ->orWhereLike('identity_users.email', sprintf('%%%s%%', $term)));
    }
}
