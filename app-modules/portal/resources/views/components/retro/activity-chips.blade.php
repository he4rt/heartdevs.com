@props (['person'])
@php ($stateColor = fn($state) => [ 'merged' => 'var(--st-merged)', 'open' => 'var(--st-open)', 'closed' => 'var(--st-closed)' ][$state ?? ''] ?? 'var(--st-open)')
<div class="acts">
    @if (count($person['pr_refs']))
        @php ($prShown = array_slice($person['pr_refs'], 0, 3))
        @php ($prMore = count($person['pr_refs']) - count($prShown))
        <div class="act act-pr" style="--c: var(--t-pr)">
            <span class="act-h">Abriu PR</span>
            <span class="act-items">
                @foreach ($prShown as $ref)
                    <a
                        class="ref"
                        @if ($ref['url']) href="{{ $ref['url'] }}" target="_blank" rel="noopener" @endif
                        title="#{{ $ref['num'] }} · {{ $ref['title'] }}"
                    >
                        <span class="stdot" style="margin: 0; background: {{ $stateColor($ref['state']) }}"></span
                        ><span class="rn">#{{ $ref['num'] }}</span><span class="rt">{{ $ref['title'] }}</span></a
                    >
                @endforeach
                @if ($prMore > 0)
                    <span class="ref more">mais {{ $prMore }}…</span>
                @endif
            </span>
        </div>
    @endif
    @if ($person['reviews'] > 0)
        <div class="act act-review" style="--c: var(--t-review)">
            <span class="act-h">Revisou</span
            ><span class="act-items"
                ><span class="ref"><span class="rn">{{ $person['reviews'] }}</span> reviews</span></span
            >
        </div>
    @endif
    @if (count($person['issue_refs']))
        @php ($issueShown = array_slice($person['issue_refs'], 0, 3))
        @php ($issueMore = count($person['issue_refs']) - count($issueShown))
        <div class="act act-issue" style="--c: var(--t-issue)">
            <span class="act-h">Abriu issue</span>
            <span class="act-items">
                @foreach ($issueShown as $ref)
                    <a
                        class="ref"
                        @if ($ref['url']) href="{{ $ref['url'] }}" target="_blank" rel="noopener" @endif
                        title="{{ $ref['title'] }}"
                    >
                        <span class="rn">#{{ $ref['num'] }}</span><span class="rt">{{ $ref['title'] }}</span></a
                    >
                @endforeach
                @if ($issueMore > 0)
                    <span class="ref more">mais {{ $issueMore }}…</span>
                @endif
            </span>
        </div>
    @endif
    @if ($person['comments'] > 0)
        <div class="act act-comment" style="--c: var(--t-comment)">
            <span class="act-h">Comentou</span
            ><span class="act-items"
                ><span class="ref"><span class="rn">{{ $person['comments'] }}</span> comentários</span></span
            >
        </div>
    @endif
    @if ($person['review_comments'] > 0)
        <div class="act act-review-comment" style="--c: var(--t-review-comment)">
            <span class="act-h">Comentou em review</span
            ><span class="act-items"
                ><span class="ref"><span class="rn">{{ $person['review_comments'] }}</span> em reviews</span></span
            >
        </div>
    @endif
    @if ($person['commits'] > 0)
        <div class="act act-commit" style="--c: var(--t-commit)">
            <span class="act-h">Commitou</span
            ><span class="act-items"
                ><span class="ref"><span class="rn">{{ $person['commits'] }}</span> commits</span></span
            >
        </div>
    @endif
</div>
