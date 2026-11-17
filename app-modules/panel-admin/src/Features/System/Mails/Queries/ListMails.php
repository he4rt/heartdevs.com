<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Mails\Queries;

use App\Models\Mail\Mail;
use App\Support\Query\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class ListMails
{
    /**
     * @param  array<array-key, mixed>  $parameters  filter[search|status], sort, page, per_page
     * @return LengthAwarePaginator<int, Mail>
     */
    public function handle(array $parameters): LengthAwarePaginator
    {
        return QueryBuilder::for(Mail::query()->withCount('attachments'), new Request($parameters))
            ->allowedFilters(
                AllowedFilter::callback('search', $this->search(...)),
                AllowedFilter::callback('status', $this->status(...)),
            )
            ->allowedSorts('created_at', 'subject')
            ->defaultSort('-created_at')
            ->orderByDesc('id')
            ->paginate(TableQuery::perPage($parameters), page: TableQuery::page($parameters));
    }

    /**
     * Matches the subject or a recipient. The recipients are a JSON list, searched as text.
     *
     * @param  Builder<Mail>  $query
     */
    private function search(Builder $query, mixed $value): void
    {
        $term = TableQuery::term($value);

        $query->where(fn (Builder $query): Builder => $query
            ->whereLike('subject', sprintf('%%%s%%', $term))
            ->orWhereRaw('cast("to" as text) ilike ?', [sprintf('%%%s%%', $term)]));
    }

    /** @param  Builder<Mail>  $query */
    private function status(Builder $query, mixed $value): void
    {
        match ($value) {
            'pending' => $query->whereNull('sent_at'),
            'sent' => $query->whereNotNull('sent_at')->whereNull('delivered_at')->whereNull('hard_bounced_at'),
            'delivered' => $query->whereNotNull('delivered_at'),
            'bounced' => $query->whereNotNull('hard_bounced_at'),
            default => null,
        };
    }
}
