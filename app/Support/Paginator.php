<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\Paginator as PaginatorInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends LengthAwarePaginator<int, mixed>
 */
final class Paginator extends LengthAwarePaginator implements PaginatorInterface
{
    /**
     * @param  LengthAwarePaginatorContract<int, mixed>  $lengthAwarePaginator
     */
    public static function paginate(LengthAwarePaginatorContract $lengthAwarePaginator): self
    {
        return new self(
            $lengthAwarePaginator->items(),
            $lengthAwarePaginator->total(),
            $lengthAwarePaginator->perPage(),
            $lengthAwarePaginator->currentPage()
        );
    }
}
