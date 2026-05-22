@php
    use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

    /** @var \He4rt\Identity\ExternalIdentity\Enums\IdentityProvider[] $supportedProviders */
    /** @var \Illuminate\Database\Eloquent\Collection<int, ExternalIdentity> $userProviders */
    /** @var string $panel */
@endphp

<div class="space-y-3">
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
            class="relative overflow-hidden rounded-xl border transition-all duration-300"
            style="{{ $connected
                ? "border-color: {$brandColor}30; box-shadow: 0 0 24px {$brandColor}08, inset 0 1px 0 {$brandColor}15;"
                : 'border-color: rgb(55 65 81 / 0.5);' }}"
        >
            {{-- Brand accent strip --}}
            <div
                class="absolute inset-y-0 left-0 w-1 rounded-l-xl transition-opacity duration-300 {{ $connected ? 'opacity-100' : 'opacity-30' }}"
                style="background-color: {{ $brandColor }}"
            ></div>

            <div class="flex items-center gap-4 p-4 pl-5">
                {{-- Provider icon with brand tint --}}
                <div class="relative shrink-0">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg"
                        style="background-color: {{ $brandColor }}15"
                    >
                        <x-filament::icon
                            :icon="$provider->getIcon()"
                            class="h-6 w-6"
                            style="color: {{ $brandColor }}"
                        />
                    </div>
                    @if ($connected)
                        <div
                            class="absolute -right-0.5 -bottom-0.5 flex h-3.5 w-3.5 items-center justify-center rounded-full ring-2 ring-gray-900"
                            style="background-color: rgb(24 24 27)"
                        >
                            <div class="h-2 w-2 rounded-full bg-emerald-400"></div>
                        </div>
                    @endif
                </div>

                {{-- Content --}}
                <div class="min-w-0 flex-1">
                    <h4 class="text-sm font-semibold text-white">{{ $provider->getLabel() }}</h4>

                    @if ($connected)
                        <div class="mt-1 flex items-center gap-2 text-sm">
                            @if ($connected->metadata['avatar'] ?? null)
                                <img
                                    src="{{ $connected->metadata['avatar'] }}"
                                    alt=""
                                    class="h-4 w-4 rounded-full"
                                    loading="lazy"
                                />
                            @endif
                            <span class="text-gray-300">{{
                                $connected->metadata['username'] ??
                                    $connected->external_account_id
                            }}</span>
                            <span class="text-gray-600">&middot;</span>
                            <span class="text-xs text-gray-500">
                                {{
                                    $connected->connected_at
                                        ->timezone(config('app.display_timezone'))
                                        ->diffForHumans()
                                }}
                            </span>
                        </div>
                    @else
                        <p class="mt-0.5 text-xs leading-relaxed text-gray-500">{{ $provider->getDescription() }}</p>
                    @endif
                </div>

                {{-- Action --}}
                <div class="shrink-0">
                    @if ($connected)
                        <x-filament::button
                            wire:click="disconnect('{{ $provider->value }}')"
                            color="danger"
                            size="sm"
                            outlined
                        >
                            Disconnect
                        </x-filament::button>
                    @else
                        <x-filament::button wire:click="connect('{{ $provider->value }}')" size="sm">
                            Connect
                        </x-filament::button>
                    @endif
                </div>
            </div>

            {{-- Expandable scopes --}}
            @if (!$connected && count($scopes) > 0)
                <div x-data="{ open: false }" class="border-t border-gray-800/80">
                    <button
                        @click="open = !open"
                        type="button"
                        class="flex w-full items-center gap-1.5 px-5 py-2 text-[11px] font-medium tracking-wider text-gray-500 uppercase transition-colors hover:text-gray-400"
                    >
                        <span class="transition-transform duration-200" :class="open && 'rotate-90'">
                            <x-filament::icon icon="heroicon-m-chevron-right" class="h-3 w-3" />
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
                        class="px-5 pb-3"
                        style="display: none"
                    >
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($scopes as $scope)
                                <code
                                    class="rounded bg-gray-800 px-1.5 py-0.5 font-mono text-[10px] text-gray-400 ring-1 ring-gray-700/50"
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
