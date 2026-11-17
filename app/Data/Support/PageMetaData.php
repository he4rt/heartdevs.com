<?php

declare(strict_types=1);

namespace App\Data\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;

final class PageMetaData extends Data
{
    public function __construct(
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
        public ?int $from,
        public ?int $to,
    ) {}

    /**
     * @param  LengthAwarePaginator<array-key, mixed>  $paginator
     */
    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        return new self(
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            from: $paginator->firstItem(),
            to: $paginator->lastItem(),
        );
    }
}
