@props (['person'])
@php
    $stats = array_values(
        array_filter(
            [
                ['cls' => 'act-pr', 'c' => '--t-pr', 'n' => count($person['pr_refs']), 'label' => 'PRs abertos'],
                ['cls' => 'act-review', 'c' => '--t-review', 'n' => $person['reviews'], 'label' => 'reviews'],
                [
                    'cls' => 'act-issue',
                    'c' => '--t-issue',
                    'n' => count($person['issue_refs']),
                    'label' => 'issues abertas',
                ],
                ['cls' => 'act-comment', 'c' => '--t-comment', 'n' => $person['comments'], 'label' => 'comentários'],
                [
                    'cls' => 'act-review-comment',
                    'c' => '--t-review-comment',
                    'n' => $person['review_comments'],
                    'label' => 'comentários em review',
                ],
                ['cls' => 'act-commit', 'c' => '--t-commit', 'n' => $person['commits'], 'label' => 'commits'],
            ],
            fn(array $stat): bool => $stat['n'] > 0,
        ),
    );
@endphp
<div class="cstats">
    @foreach ($stats as $stat)
        <span
            class="cstat {{ $stat['cls'] }}"
            style="--c: var({{ $stat['c'] }})"
            title="{{ $stat['n'] }} {{ $stat['label'] }}"
        >
            <span class="cstat-n">{{ $stat['n'] }}</span>
        </span>
    @endforeach
</div>
