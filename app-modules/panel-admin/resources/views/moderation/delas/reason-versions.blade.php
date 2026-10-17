{{--
    Versões do motivo de uma decisão, numa linha do tempo: a original primeiro,
    a que vale hoje por último. Cinza = original, azul = edição, verde = vale hoje.
--}}
<ol class="relative ms-2 border-s-2 border-gray-300 dark:border-white/25">
    @foreach ($versions as $version)
        @php
            $isOriginal = $loop->first;
            $isCurrent = $loop->last;
            $label = $isOriginal
                ? __('panel-admin::delas.actions.version_original')
                : __('panel-admin::delas.actions.version_edit', ['number' => $loop->index]);
            $writtenAt = $version->created_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i');
        @endphp

        <li class="relative ps-6 pb-5 last:pb-0">
            <span
                @class([
                    'absolute -start-[6px] top-1.5 size-2.5 rounded-full ring-4 ring-white dark:ring-gray-900',
                    'bg-success-500' => $isCurrent,
                    'bg-gray-400' => $isOriginal && ! $isCurrent,
                    'bg-info-500' => ! $isOriginal && ! $isCurrent,
                ])
            ></span>

            <div class="flex flex-wrap items-center gap-2">
                <x-filament::badge :color="$isOriginal ? 'gray' : 'info'" size="sm">{{ $label }}</x-filament::badge>

                @if ($isCurrent)
                    <x-filament::badge color="success" size="sm">
                        {{ __('panel-admin::delas.actions.version_current') }}
                    </x-filament::badge>
                @endif

                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $version->actor->name ?? '—' }} · {{ $writtenAt }}
                </span>
            </div>

            <p class="mt-1.5 text-sm text-gray-950 dark:text-white">
                <span class="font-medium text-gray-500 dark:text-gray-400">
                    {{ __('panel-admin::delas.actions.message_label') }}
                </span>
                <span class="whitespace-pre-line">{{ $version->reason }}</span>
            </p>
        </li>
    @endforeach
</ol>
