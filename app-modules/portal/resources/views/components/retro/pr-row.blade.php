@props (['pr'])
@php ($stateColor = ['merged' => 'var(--st-merged)', 'open' => 'var(--st-open)', 'closed' => 'var(--st-closed)'][ $pr['state'] ?? '' ] ?? 'var(--st-open)')
<a class="tpr" @if ($pr['url']) href="{{ $pr['url'] }}" target="_blank" rel="noopener" @endif>
    <span class="stdot" style="background: {{ $stateColor }}"></span>
    <span class="rn">#{{ $pr['num'] }}</span>
    <span style="flex: 1; min-width: 0">
        <span class="d">{{
            $pr['title'] !== ''
                ? $pr['title']
                : 'PR #' . $pr['num']
        }}</span>
        <span style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 7px">
            <span class="by">{{ '@' . $pr['author_login'] }}</span>
            <x-portal::retro.badges :additions="$pr['additions']" :deletions="$pr['deletions']" />
        </span>
    </span>
</a>
