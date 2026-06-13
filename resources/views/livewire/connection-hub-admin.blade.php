@php
    use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

    /** @var \He4rt\Identity\ExternalIdentity\Enums\IdentityProvider[] $supportedProviders */
    /** @var \Illuminate\Database\Eloquent\Collection<int, ExternalIdentity> $tenantProviders */
    /** @var string $panel */
@endphp

<div class="space-y-4">
    @foreach ($supportedProviders as $provider)
        @php
            $brandColor = match ($provider->value) {
                'github' => '#8b949e',
                'discord' => '#5865F2',
                'twitch' => '#9146FF',
                default => '#6b7280',
            };

            $connections = $tenantProviders->filter(fn(ExternalIdentity $c) => $c->provider === $provider);

            $scopes = $provider->getScopes($panel);
        @endphp
        <div wire:key="provider-{{ $provider->value }}" class="overflow-hidden rounded-xl border border-gray-700/50">
            {{-- Provider header --}}
            <div class="flex items-center gap-3 bg-gray-800/50 px-4 py-3">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                    style="background-color: {{ $brandColor }}20"
                >
                    <x-filament::icon :icon="$provider->getIcon()" class="h-5 w-5" style="color: {{ $brandColor }}" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline gap-2">
                        <h4 class="text-sm font-semibold text-white">{{ $provider->getLabel() }}</h4>
                        @if ($connections->isNotEmpty())
                            <span class="text-[11px] font-medium" style="color: {{ $brandColor }}">
                                {{ $connections->count() }} {{ $connections->count() === 1 ? 'account' : 'accounts' }}
                            </span>
                        @else
                            <span class="text-[11px] text-gray-500">No accounts linked</span>
                        @endif
                    </div>
                </div>

                <x-filament::button wire:click="connect('{{ $provider->value }}')" size="sm" outlined>
                    <x-filament::icon icon="heroicon-m-plus" class="mr-1 -ml-1 h-4 w-4" />
                    Add
                </x-filament::button>
            </div>

            {{-- Connections table --}}
            @if ($connections->isNotEmpty())
                <div class="divide-y divide-gray-800/60">
                    {{-- Table header --}}
                    <div class="grid grid-cols-[1fr_auto_auto] items-center gap-4 px-4 py-2">
                        <span class="text-[10px] font-medium tracking-wider text-gray-500 uppercase">Account</span>
                        <span class="text-[10px] font-medium tracking-wider text-gray-500 uppercase">Connected</span>
                        <span class="w-8"></span>
                    </div>

                    {{-- Table rows --}}
                    @foreach ($connections as $connection)
                        <div
                            wire:key="connection-{{ $connection->id }}"
                            class="grid grid-cols-[1fr_auto_auto] items-center gap-4 px-4 py-3 transition-colors hover:bg-white/[0.02]"
                        >
                            {{-- Account info --}}
                            <div class="flex items-center gap-3 overflow-hidden">
                                @if ($connection->metadata['avatar'] ?? null)
                                    <img
                                        src="{{ $connection->metadata['avatar'] }}"
                                        alt=""
                                        class="h-8 w-8 shrink-0 rounded-full ring-1 ring-white/10"
                                        loading="lazy"
                                    />
                                @else
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-700 text-xs font-medium text-gray-400"
                                    >
                                        {{
                                            strtoupper(
                                                substr($connection->metadata['username'] ?? '?', 0, 1),
                                            )
                                        }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="truncate text-sm font-medium text-gray-200">
                                            {{
                                                $connection->metadata['username'] ??
                                                    $connection->external_account_id
                                            }}
                                        </span>
                                        @if ($connection->connectedByUser)
                                            <span class="shrink-0 text-xs text-gray-500"
                                                >&middot; {{ $connection->connectedByUser->name }}</span
                                            >
                                        @endif
                                    </div>
                                    @if ($connection->metadata['email'] ?? null)
                                        <span
                                            class="text-[11px] text-gray-500"
                                            >{{ $connection->metadata['email'] }}</span
                                        >
                                    @endif
                                </div>
                            </div>

                            {{-- Date --}}
                            <span class="text-xs whitespace-nowrap text-gray-500">
                                {{
                                    $connection->connected_at
                                        ->timezone(config('app.display_timezone'))
                                        ->format('M d, Y')
                                }}
                            </span>

                            {{-- Actions --}}
                            <x-filament::icon-button
                                wire:click="disconnectById('{{ $connection->id }}')"
                                icon="heroicon-m-x-mark"
                                color="danger"
                                size="sm"
                                tooltip="Disconnect"
                            />
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Expandable scopes --}}
            @if (count($scopes) > 0)
                <div x-data="{ open: false }" class="border-t border-gray-800/60 bg-gray-900/30">
                    <button
                        @click="open = !open"
                        type="button"
                        class="flex w-full items-center gap-1.5 px-4 py-2 text-[10px] font-medium tracking-wider text-gray-500 uppercase transition-colors hover:text-gray-400"
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
                        class="px-4 pb-3"
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
