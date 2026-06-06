@props (['person'])
@php ($stateColor = fn($state) => [ 'merged' => 'var(--st-merged)', 'open' => 'var(--st-open)', 'closed' => 'var(--st-closed)' ][$state ?? ''] ?? 'var(--st-open)')
<div class="acts">
    @if (count($person['pr_refs']))
        <div class="act" style="--c: var(--t-pr)">
            <span class="act-h"
                <svg class="act-ic" viewBox="0 0 16 16" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M1.5 3.25a2.25 2.25 0 1 1 3 2.122v5.256a2.251 2.251 0 1 1-1.5 0V5.372A2.25 2.25 0 0 1 1.5 3.25Zm5.677-.177L9.573.677A.25.25 0 0 1 10 .854V2.5h1A2.5 2.5 0 0 1 13.5 5v5.628a2.251 2.251 0 1 1-1.5 0V5a1 1 0 0 0-1-1h-1v1.646a.25.25 0 0 1-.427.177L7.177 3.427a.25.25 0 0 1 0-.354ZM3.75 2.5a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Zm0 9.5a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Zm8.25.75a.75.75 0 1 0 1.5 0 .75.75 0 0 0-1.5 0Z" /></svg>
                >Abriu PR</span
            >
            <span class="act-items">
                @foreach ($person['pr_refs'] as $ref)
                    <a
                        class="ref"
                        @if ($ref['url']) href="{{ $ref['url'] }}" target="_blank" rel="noopener" @endif
                        title="#{{ $ref['num'] }} · {{ $ref['title'] }}"
                    >
                        <span class="stdot" style="margin: 0; background: {{ $stateColor($ref['state']) }}"></span
                        ><span class="rn">#{{ $ref['num'] }}</span><span class="rt">{{ $ref['title'] }}</span></a
                    >
                @endforeach
            </span>
        </div>
    @endif
    @if ($person['reviews'] > 0)
        <div class="act" style="--c: var(--t-review)">
            <span class="act-h"
                <svg class="act-ic" viewBox="0 0 16 16" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M8 2c1.981 0 3.671.992 4.933 2.078 1.27 1.091 2.187 2.345 2.637 3.023a1.62 1.62 0 0 1 0 1.798c-.45.678-1.367 1.932-2.637 3.023C11.671 13.008 9.981 14 8 14c-1.981 0-3.671-.992-4.933-2.078C1.797 10.831.88 9.577.43 8.899a1.62 1.62 0 0 1 0-1.798c.45-.677 1.367-1.931 2.637-3.023C4.329 2.992 6.019 2 8 2ZM1.679 7.932a.12.12 0 0 0 0 .136c.411.622 1.241 1.75 2.366 2.717C5.176 11.758 6.527 12.5 8 12.5c1.473 0 2.825-.742 3.955-1.715 1.124-.967 1.954-2.096 2.366-2.717a.12.12 0 0 0 0-.136c-.412-.621-1.242-1.75-2.366-2.717C10.824 4.242 9.473 3.5 8 3.5c-1.473 0-2.825.742-3.955 1.715-1.124.967-1.954 2.096-2.366 2.717ZM8 10a2 2 0 1 1-.001-3.999A2 2 0 0 1 8 10Z" /></svg>
                >Revisou</span
            ><span class="act-items"
                ><span class="ref"><span class="rn">{{ $person['reviews'] }}</span> reviews</span></span
            >
        </div>
    @endif
    @if (count($person['issue_refs']))
        <div class="act" style="--c: var(--t-issue)">
            <span class="act-h"
                <svg class="act-ic" viewBox="0 0 16 16" width="14" height="14" fill="currentColor" aria-hidden="true">
                    <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                    <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0ZM1.5 8a6.5 6.5 0 1 0 13 0 6.5 6.5 0 0 0-13 0Z" />
                </svg>
                >Abriu issue</span
            >
            <span class="act-items">
                @foreach ($person['issue_refs'] as $ref)
                    <a
                        class="ref"
                        @if ($ref['url']) href="{{ $ref['url'] }}" target="_blank" rel="noopener" @endif
                        title="{{ $ref['title'] }}"
                    >
                        <span class="rn">#{{ $ref['num'] }}</span><span class="rt">{{ $ref['title'] }}</span></a
                    >
                @endforeach
            </span>
        </div>
    @endif
    @if ($person['comments'] > 0)
        <div class="act" style="--c: var(--t-comment)">
            <span class="act-h"
                <svg class="act-ic" viewBox="0 0 16 16" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M1 2.75C1 1.784 1.784 1 2.75 1h10.5c.966 0 1.75.784 1.75 1.75v7.5A1.75 1.75 0 0 1 13.25 12H9.06l-2.573 2.573A1.458 1.458 0 0 1 4 13.543V12H2.75A1.75 1.75 0 0 1 1 10.25Zm1.75-.25a.25.25 0 0 0-.25.25v7.5c0 .138.112.25.25.25h2a.75.75 0 0 1 .75.75v2.19l2.72-2.72a.749.749 0 0 1 .53-.22h4.5a.25.25 0 0 0 .25-.25v-7.5a.25.25 0 0 0-.25-.25Z" /></svg>
                >Comentou</span
            ><span class="act-items"
                ><span class="ref"><span class="rn">{{ $person['comments'] }}</span> comentários</span></span
            >
        </div>
    @endif
    @if ($person['commits'] > 0)
        <div class="act" style="--c: var(--t-commit)">
            <span class="act-h"
                <svg class="act-ic" viewBox="0 0 16 16" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M11.93 8.5a4.002 4.002 0 0 1-7.86 0H.75a.75.75 0 0 1 0-1.5h3.32a4.002 4.002 0 0 1 7.86 0h3.32a.75.75 0 0 1 0 1.5Zm-1.43-.75a2.5 2.5 0 1 0-5 0 2.5 2.5 0 0 0 5 0Z" /></svg>
                >Commitou</span
            ><span class="act-items"
                ><span class="ref"><span class="rn">{{ $person['commits'] }}</span> commits</span></span
            >
        </div>
    @endif
</div>
