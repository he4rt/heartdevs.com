@props (['people'])
@php ($top = array_slice($people, 0, 5))
<section class="slide" data-label="O núcleo">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>O núcleo</span>
        <h2 class="sec" data-anim>Quem puxou a frente</h2>
        <p class="sec-sub" data-anim>As pessoas que concentraram o grosso da entrega — abrindo PRs, revisando código umas das outras e mantendo as discussões vivas.</p>
        @if (count($top))
            <div data-anim style="margin-top: 20px">
                <x-portal::retro.person-card :person="$top[0]" :rank="1" :size="84" />
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-top: 14px">
                @foreach (array_slice($top, 1) as $i => $person)
                    <div data-anim><x-portal::retro.person-card :person="$person" :rank="$i + 2" :size="48" /></div>
                @endforeach
            </div>
        @endif
    </div>
</section>
