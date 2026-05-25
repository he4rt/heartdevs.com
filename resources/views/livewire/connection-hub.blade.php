@php
    use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

    /** @var \He4rt\Identity\ExternalIdentity\Enums\IdentityProvider[] $supportedProviders */
    /** @var \Illuminate\Database\Eloquent\Collection<int, ExternalIdentity> $userProviders */
    /** @var string $panel */
@endphp

<div class="space-y-2">
    @foreach ($supportedProviders as $provider)
        @php
            $connected = $userProviders
                ->filter(fn(ExternalIdentity $c) => $c->provider === $provider && $c->isConnected())
                ->first();

            $brandColor = match ($provider->value) {
                'github' => '#8b949e',
                'discord' => '#5865F2',
                'twitch' => '#9146FF',
                default => '#6b7280',
            };

            $scopes = $provider->getScopes($panel);
        @endphp
        <div
            wire:key="provider-{{ $provider->value }}"
            class="relative overflow-hidden rounded-lg border transition-all duration-200"
            style="{{ $connected
                ? "border-color: {$brandColor}30;"
                : 'border-color: rgb(55 65 81 / 0.4);' }}"
        >
            <div class="flex items-center gap-2.5 p-2.5">
                {{-- Provider icon --}}
                <div class="relative shrink-0">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-md"
                        style="background-color: {{ $brandColor }}15"
                    >
                        <x-filament::icon
                            :icon="$provider->getIcon()"
                            class="h-4 w-4"
                            style="color: {{ $brandColor }}"
                        />
                    </div>
                    @if ($connected)
                        <div
                            class="absolute -right-0.5 -bottom-0.5 h-2.5 w-2.5 rounded-full border-2 border-gray-900 bg-emerald-400"
                        ></div>
                    @endif
                </div>

                {{-- Content --}}
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <span class="truncate text-sm font-medium text-white">{{ $provider->getLabel() }}</span>

                        @if ($connected)
                            <button
                                wire:click="disconnect('{{ $provider->value }}')"
                                type="button"
                                class="shrink-0 text-xs text-gray-500 transition-colors hover:text-red-400"
                            >
                                Disconnect
                            </button>
                        @else
                            <x-filament::button
                                wire:click="connect('{{ $provider->value }}')"
                                size="sm"
                                class="shrink-0"
                            >
                                Connect
                            </x-filament::button>
                        @endif
                    </div>

                    @if ($connected)
                        <div class="mt-0.5 flex items-center gap-1.5 text-xs">
                            @if ($connected->metadata['avatar'] ?? null)
                                <img
                                    src="{{ $connected->metadata['avatar'] }}"
                                    alt=""
                                    class="h-3.5 w-3.5 rounded-full"
                                    loading="lazy"
                                />
                            @endif
                            <span class="truncate text-gray-300">{{
                                $connected->metadata['username'] ??
                                    $connected->external_account_id
                            }}</span>
                            <span class="text-gray-600">&middot;</span>
                            <span class="shrink-0 text-gray-500">
                                {{
                                    $connected->connected_at
                                        ->timezone(config('app.display_timezone'))
                                        ->diffForHumans()
                                }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Permissions --}}
            @if (!$connected && count($scopes) > 0)
                <div x-data="{ open: false }" class="border-t border-gray-800/50">
                    <button
                        @click="open = !open"
                        type="button"
                        class="flex w-full items-center gap-1 px-2.5 py-1.5 text-[10px] font-medium tracking-wider text-gray-500 uppercase transition-colors hover:text-gray-400"
                    >
                        <span class="transition-transform duration-200" :class="open && 'rotate-90'">
                            <x-filament::icon icon="heroicon-m-chevron-right" class="h-2.5 w-2.5" />
                        </span>
                        <span>Permissions</span>
                        <span class="text-gray-600">({{ count($scopes) }})</span>
                    </button>
                    <div
                        x-show="open"
                        x-transition:enter="transition-all duration-200 ease-out"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition-all duration-150 ease-in"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="px-2.5 pb-2"
                        style="display: none"
                    >
                        <div class="flex flex-wrap gap-1">
                            @foreach ($scopes as $scope)
                                <code
                                    class="rounded bg-gray-800/80 px-1 py-0.5 font-mono text-[9px] text-gray-400 ring-1 ring-gray-700/50"
                                >
                                    {{ $scope }}
                                </code>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>
