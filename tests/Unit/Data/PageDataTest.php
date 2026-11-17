<?php

declare(strict_types=1);

use App\Data\Navigation\NavigationItemData;
use App\Data\Support\PageData;
use Illuminate\Pagination\LengthAwarePaginator;

it('wraps a paginator into data and meta', function (): void {
    $paginator = new LengthAwarePaginator(items: ['a', 'b'], total: 30, perPage: 2, currentPage: 3);

    $page = PageData::fromPaginator($paginator, fn (string $key): NavigationItemData => new NavigationItemData(
        key: $key,
        label: mb_strtoupper($key),
        icon: 'circle',
        href: '/'.$key,
        current: false,
    ));

    expect($page->toArray())
        ->data->toHaveCount(2)
        ->and($page->toArray()['data'][1]['label'])->toBe('B')
        ->and($page->toArray()['meta'])->toBe([
            'currentPage' => 3,
            'lastPage' => 15,
            'perPage' => 2,
            'total' => 30,
            'from' => 5,
            'to' => 6,
        ]);
});
