@props (['person'])
@php
    $segments = array_values(
        array_filter(
            [
                ['color' => 'var(--t-pr)', 'count' => $person['prs'], 'label' => 'PRs'],
                ['color' => 'var(--t-review)', 'count' => $person['reviews'], 'label' => 'reviews'],
                ['color' => 'var(--t-issue)', 'count' => $person['issues'], 'label' => 'issues'],
                ['color' => 'var(--t-comment)', 'count' => $person['comments'], 'label' => 'comentários'],
                ['color' => 'var(--t-commit)', 'count' => $person['commits'], 'label' => 'commits'],
            ],
            fn(array $segment): bool => $segment['count'] > 0,
        ),
    );
    $sum = array_sum(array_column($segments, 'count')) ?: 1;
@endphp
@if (count($segments))
    <div class="compbar" aria-hidden="true">
        @foreach ($segments as $segment)
            <span
                class="seg"
                style="width: {{ round(($segment['count'] / $sum) * 100, 3) }}%; background: {{ $segment['color'] }}"
                title="{{ $segment['count'] }} {{ $segment['label'] }}"
            ></span>
        @endforeach
    </div>
@endif
