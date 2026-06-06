@props (['person'])
@php ($stateColor = fn($state) => [ 'merged' => 'var(--st-merged)', 'open' => 'var(--st-open)', 'closed' => 'var(--st-closed)' ][$state ?? ''] ?? 'var(--st-open)')
<div class="acts">
    @if (count($person['pr_refs']))
        <div class="act" style="--c: var(--t-pr)">
            <span class="act-h">Abriu PR</span>
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
            <span class="act-h">Revisou</span
            ><span class="act-items"
                ><span class="ref"><span class="rn">{{ $person['reviews'] }}</span> reviews</span></span
            >
        </div>
    @endif
    @if (count($person['issue_refs']))
        <div class="act" style="--c: var(--t-issue)">
            <span class="act-h">Abriu issue</span>
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
            <span class="act-h">Comentou</span
            ><span class="act-items"
                ><span class="ref"><span class="rn">{{ $person['comments'] }}</span> comentários</span></span
            >
        </div>
    @endif
    @if ($person['commits'] > 0)
        <div class="act" style="--c: var(--t-commit)">
            <span class="act-h">Commitou</span
            ><span class="act-items"
                ><span class="ref"><span class="rn">{{ $person['commits'] }}</span> commits</span></span
            >
        </div>
    @endif
</div>
