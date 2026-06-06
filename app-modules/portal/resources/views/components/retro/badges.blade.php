@props (['additions' => 0, 'deletions' => 0, 'files' => null])
@php ($total = max(1, $additions + $deletions))
<span class="szbar"
    ><span class="a" style="width: {{ round($additions / $total * 100, 1) }}%"></span
    ><span class="d" style="width: {{ round($deletions / $total * 100, 1) }}%"></span
></span>
@if ($additions > 0)
    <span class="bdg add">+{{ number_format($additions, 0, ',', '.') }}</span>
@endif
@if ($deletions > 0)
    <span class="bdg del">−{{ number_format($deletions, 0, ',', '.') }}</span>
@endif
@if (!is_null($files))
    <span class="bdg neu">{{ $files }} arq.</span>
@endif
