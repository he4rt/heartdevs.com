<?php

declare(strict_types=1);

namespace App\Data\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;

final class PageData extends Data
{
    /**
     * @param  array<int, Data>  $data
     */
    public function __construct(
        public array $data,
        public PageMetaData $meta,
    ) {}

    /**
     * @template TModel
     *
     * @param  LengthAwarePaginator<array-key, TModel>  $paginator
     * @param  callable(TModel): Data  $toData
     */
    public static function fromPaginator(LengthAwarePaginator $paginator, callable $toData): self
    {
        return new self(
            $paginator->getCollection()->map(fn (mixed $model): Data => $toData($model))->values()->all(),
            PageMetaData::fromPaginator($paginator),
        );
    }
}
