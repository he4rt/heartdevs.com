<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Activity\Queries;

use App\Support\Query\TableQuery;
use He4rt\PanelAdmin\Features\System\Activity\Data\ActivityRowData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class ListActivities
{
    /**
     * @param  array<array-key, mixed>  $parameters  filter[search|event|subject_type|risk_level], sort, page, per_page
     * @return LengthAwarePaginator<int, Activity>
     */
    public function handle(array $parameters): LengthAwarePaginator
    {
        return QueryBuilder::for(Activity::query()->with(['causer', 'subject']), new Request($parameters))
            ->allowedFilters(
                AllowedFilter::callback('search', $this->search(...)),
                AllowedFilter::exact('event'),
                AllowedFilter::exact('subject_type'),
                AllowedFilter::exact('risk_level'),
            )
            ->allowedSorts('created_at', 'risk_score')
            ->defaultSort('-created_at')
            ->orderByDesc('id')
            ->paginate(TableQuery::perPage($parameters), page: TableQuery::page($parameters));
    }

    /** @return array{events: array<int, string>, subjectTypes: array<int, array{value: string, label: string}>} */
    public function filterOptions(): array
    {
        return [
            'events' => $this->distinct('event'),
            'subjectTypes' => collect($this->distinct('subject_type'))
                ->map(fn (string $type): array => ['value' => $type, 'label' => ActivityRowData::typeLabel($type) ?? $type])
                ->values()
                ->all(),
        ];
    }

    /** @return array<int, string> */
    private function distinct(string $column): array
    {
        return Activity::query()->whereNotNull($column)->distinct()->orderBy($column)->pluck($column)->map(strval(...))->values()->all();
    }

    /** @param  Builder<Activity>  $query */
    private function search(Builder $query, mixed $value): void
    {
        $term = TableQuery::term($value);

        $query->whereLike('description', sprintf('%%%s%%', $term));
    }
}
