@php
    /** @var array{id: string, login: string}|null $broadcaster */
    /** @var string $callbackUrl */
    /** @var string $secret */
    /** @var array<string, array{label: string, types: array<int, array{value: string, name: string, version: string, exists: bool}>}> $groups */

    $totalTypes = collect($groups)->sum(fn($g) => count($g['types']));
    $existingCount = collect($groups)->sum(fn($g) => collect($g['types'])->where('exists', true)->count());
    $newCount = $totalTypes - $existingCount;
@endphp

<div class="space-y-6">
    {{-- Warning if no broadcaster --}}
    @if (!$broadcaster)
        <div class="flex items-start gap-3 rounded-xl border border-red-500/30 bg-red-500/5 p-4">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-red-400" />
            <div>
                <p class="text-sm font-medium text-red-400">No Twitch account connected</p>
                <p class="mt-1 text-xs text-red-400/70">Connect your Twitch account with the <span class="font-medium">“Connect Twitch”</span> button on this page before registering subscriptions.</p>
            </div>
        </div>
    @else
        {{-- Configuration summary --}}
        <div class="rounded-xl border border-gray-700/50 bg-gray-800/30">
            <div class="border-b border-gray-700/50 px-4 py-3">
                <h4 class="text-xs font-semibold tracking-wider text-gray-400 uppercase">Configuration</h4>
            </div>
            <div class="grid grid-cols-1 gap-px bg-gray-700/30 sm:grid-cols-3">
                <div class="bg-gray-900/50 px-4 py-3">
                    <span class="text-[10px] font-medium tracking-wider text-gray-500 uppercase">Broadcaster</span>
                    <div class="mt-1 flex items-center gap-1.5">
                        <x-filament::icon icon="fab-twitch" class="h-4 w-4 text-purple-400" />
                        <span class="text-sm font-medium text-gray-200">{{ $broadcaster['login'] }}</span>
                        <span class="text-xs text-gray-500">({{ $broadcaster['id'] }})</span>
                    </div>
                </div>
                <div class="bg-gray-900/50 px-4 py-3">
                    <span class="text-[10px] font-medium tracking-wider text-gray-500 uppercase">Callback URL</span>
                    <div class="mt-1">
                        <code class="text-xs break-all text-gray-300">{{ $callbackUrl }}</code>
                    </div>
                </div>
                <div class="bg-gray-900/50 px-4 py-3">
                    <span class="text-[10px] font-medium tracking-wider text-gray-500 uppercase">Secret</span>
                    <div class="mt-1">
                        <code class="font-mono text-xs text-gray-300">{{ $secret }}</code>
                    </div>
                </div>
            </div>
        </div>
        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-lg border border-gray-700/50 px-4 py-3 text-center">
                <div class="text-2xl font-bold text-white">{{ $totalTypes }}</div>
                <div class="text-[10px] font-medium tracking-wider text-gray-500 uppercase">Total Types</div>
            </div>
            <div class="rounded-lg border border-emerald-500/20 bg-emerald-500/5 px-4 py-3 text-center">
                <div class="text-2xl font-bold text-emerald-400">{{ $existingCount }}</div>
                <div class="text-[10px] font-medium tracking-wider text-emerald-500/70 uppercase">Already Active</div>
            </div>
            <div class="rounded-lg border border-purple-500/20 bg-purple-500/5 px-4 py-3 text-center">
                <div class="text-2xl font-bold text-purple-400">{{ $newCount }}</div>
                <div class="text-[10px] font-medium tracking-wider text-purple-500/70 uppercase">Will Create</div>
            </div>
        </div>
        {{-- Event type groups --}}
        <div class="space-y-3">
            <h4 class="text-xs font-semibold tracking-wider text-gray-400 uppercase">Event Types</h4>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($groups as $key => $group)
                    <div class="rounded-xl border border-gray-700/50 bg-gray-800/20">
                        <div class="flex items-center justify-between border-b border-gray-700/30 px-3 py-2">
                            <span class="text-xs font-semibold text-gray-300">{{ $group['label'] }}</span>
                            <span class="text-[10px] text-gray-500">{{ count($group['types']) }}</span>
                        </div>
                        <div class="p-2">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($group['types'] as $type)
                                    <span
                                        @class ([
                                            'inline-flex items-center gap-1 rounded px-1.5 py-0.5 font-mono text-[10px] ring-1',
                                            'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20' => $type['exists'],
                                            'bg-gray-800 text-gray-400 ring-gray-700/50' => !$type['exists']
                                        ])
                                    >
                                        @if ($type['exists'])
                                            <x-filament::icon icon="heroicon-m-check" class="h-2.5 w-2.5" />
                                        @endif
                                        {{ $type['value'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
