@php
    use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

    /** @var \He4rt\Identity\ExternalIdentity\Enums\IdentityProvider[] $supportedProviders */
    /** @var \Illuminate\Database\Eloquent\Collection<int, ExternalIdentity> $userProviders */
    /** @var string $panel */
    /** @var array<string, mixed>|null $mergeTarget */
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
            @class ([
                'relative overflow-hidden rounded-lg border transition-all duration-200',
                'border-gray-200 dark:border-gray-700/40' => !$connected
            ])
            @if ($connected) style="border-color: {{ $brandColor }}30;" @endif
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
                            class="absolute -right-0.5 -bottom-0.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-emerald-400 dark:border-gray-900"
                        ></div>
                    @endif
                </div>

                {{-- Content --}}
                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline gap-2">
                        <span
                            class="truncate text-sm font-medium text-gray-900 dark:text-white"
                            >{{ $provider->getLabel() }}</span
                        >
                        @if ($connected)
                            <span class="shrink-0 text-[10px] text-gray-400 dark:text-gray-500">
                                {{
                                    $connected->connected_at
                                        ->timezone(config('app.display_timezone'))
                                        ->diffForHumans()
                                }}
                            </span>
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
                            <span class="truncate text-gray-500 dark:text-gray-300">{{
                                $connected->metadata['username'] ??
                                    $connected->external_account_id
                            }}</span>
                        </div>
                    @endif
                </div>

                {{-- Action --}}
                @if ($connected)
                    <button
                        wire:click="disconnect('{{ $provider->value }}')"
                        type="button"
                        class="shrink-0 rounded-md px-2 py-0.5 text-[11px] font-medium text-gray-500 ring-1 ring-gray-300 transition-all hover:text-red-500 hover:ring-red-400/40 dark:text-gray-400 dark:ring-gray-700/60 dark:hover:text-red-400 dark:hover:ring-red-500/40"
                    >
                        Disconnect
                    </button>
                @else
                    <x-filament::button wire:click="connect('{{ $provider->value }}')" size="sm" class="shrink-0">
                        Connect
                    </x-filament::button>
                @endif
            </div>

            {{-- Permissions --}}
            @if (!$connected && count($scopes) > 0)
                <div x-data="{ open: false }" class="border-t border-gray-200 dark:border-gray-800/50">
                    <button
                        @click="open = !open"
                        type="button"
                        class="flex w-full items-center gap-1 px-2.5 py-1.5 text-[10px] font-medium tracking-wider text-gray-400 uppercase transition-colors hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-400"
                    >
                        <span class="transition-transform duration-200" :class="open && 'rotate-90'">
                            <x-filament::icon icon="heroicon-m-chevron-right" class="h-2.5 w-2.5" />
                        </span>
                        <span>Permissions</span>
                        <span class="text-gray-300 dark:text-gray-600">({{ count($scopes) }})</span>
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
                                    class="rounded bg-gray-100 px-1 py-0.5 font-mono text-[9px] text-gray-500 ring-1 ring-gray-200 dark:bg-gray-800/80 dark:text-gray-400 dark:ring-gray-700/50"
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

    {{-- Merge Confirmation Modal --}}
    @if ($showMergeModal && $mergeTarget)
        <div
            x-data="{ open: true }"
            x-show="open"
            x-transition
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
        >
            <div
                class="w-full max-w-sm rounded-xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="mb-4 flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-link" class="h-5 w-5 text-amber-400" />
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Conta existente encontrada</h3>
                </div>

                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">Já existe uma conta vinculada a esse provedor:</p>

                <div
                    class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700/60 dark:bg-gray-800/50"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-900 dark:text-white"
                            >@ {{ $mergeTarget['username'] }}</span
                        >
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $mergeTarget['created_at'] }}</span>
                    </div>
                    @if ($mergeTarget['messages_count'] > 0)
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ number_format($mergeTarget['messages_count']) }} mensagens
                        </div>
                    @endif
                </div>

                <div
                    class="mb-5 flex items-start gap-3 rounded-lg border border-amber-300/40 bg-amber-50/80 p-3 dark:border-amber-500/20 dark:bg-amber-500/5"
                >
                    <x-filament::icon
                        icon="heroicon-o-exclamation-triangle"
                        class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                    />
                    <div>
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">
                            A conta @ {{ $mergeTarget['username'] }} será mantida como principal.
                        </p>
                        <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-400/70">
                            Sua conta atual @ {{ auth()->user()->username }} será absorvida e removida. O histórico já associado à conta mantida será preservado. Ao finalizar, você será autenticado novamente na conta @ {{ $mergeTarget['username'] }}.
                        </p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <x-filament::button wire:click="confirmMerge" color="warning" class="flex-1">
                        Unificar
                    </x-filament::button>
                    <x-filament::button wire:click="cancelMerge" color="gray" class="flex-1">
                        Cancelar
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</div>
