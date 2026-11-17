<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\FeatureFlags\Queries;

use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\StoredFeatureFlag;
use App\Support\Query\TableQuery;
use He4rt\Identity\Teams\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class ListFeatureFlagTeams
{
    /**
     * @param  array<array-key, mixed>  $parameters  filter[search|enabled], sort, page, per_page
     * @return LengthAwarePaginator<int, Team>
     */
    public function handle(FeatureFlag $flag, array $parameters): LengthAwarePaginator
    {
        $enabling = StoredFeatureFlag::query()
            ->switchedOn($flag)
            ->whereRaw('feature_flags.scope = concat(cast(? as text), identity_teams.id)', [(new Team)->getMorphClass().'|']);

        $teams = Team::query()
            ->select('identity_teams.*')
            ->addSelect(['feature_enabled' => $enabling->clone()->selectRaw('true')->limit(1)]);

        return QueryBuilder::for($teams, new Request($parameters))
            ->allowedFilters(
                AllowedFilter::callback('search', $this->search(...)),
                AllowedFilter::callback('enabled', fn (Builder $query, mixed $value): Builder => TableQuery::term($value) === 'on'
                    ? $query->whereExists($enabling->toBase())
                    : $query->whereNotExists($enabling->toBase())),
            )
            ->allowedSorts('name')
            ->defaultSort('name')
            ->orderBy('identity_teams.id')
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
