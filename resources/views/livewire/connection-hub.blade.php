@php
    use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
@endphp

@props (['supportedProviders', 'userProviders'])

@php
    /** @var \He4rt\Identity\ExternalIdentity\Enums\IdentityProvider[] $supportedProviders */
    /** @var \Illuminate\Support\Collection<int, ExternalIdentity> $userProviders */
@endphp

<div data-slot="card-content" class="space-y-3 px-6">
    @foreach ($supportedProviders as $provider)
        @php
            $connectedProvider = $userProviders
                ->filter(fn(ExternalIdentity $connection) => $connection->provider == $provider->value)
                ->first();
        @endphp
        <x-filament::section :secondary="$connectedProvider">
            <div class="flex flex-1 items-center gap-3">
                <div class="bg-muted mt-0.5 rounded-lg p-2">
                    <x-filament::icon :icon="$provider->getIcon()" class="h-12 w-12" />
                </div>
                <div class="flex-1 space-y-1">
                    <div class="flex items-center gap-2">
                        <h4 class="text-sm font-semibold">{{ $provider->getLabel() }}</h4>
                        @if ($connectedProvider)
                            <x-filament::badge color="green">Connected</x-filament::badge>
                        @endif
                    </div>
                    <p class="text-muted-foreground text-xs leading-relaxed">{{ $provider->getDescription() }}</p>
                    <p class="text-muted-foreground text-xs">
                        @if ($connectedProvider)
                            Connected at
                            <strong>{{
                                $connectedProvider->updated_at
                                    ->timezone(config('app.display_timezone'))
                                    ->format('d/m/Y H:i:s')
                            }}</strong>
                        @else
                            Nessa autenticação iremos pedir acesso à:
                            @foreach ($provider->getScopes($panel) as $scope)
                                <x-filament::badge color="orange">{{ $scope }}</x-filament::badge>
                            @endforeach
                        @endif
                    </p>
                </div>
                <x-filament::button
                    wire:click="{{ !$connectedProvider ? 'connect' : 'disconnect' }}('{{ $provider->value }}')"
                    :color="$connectedProvider ? 'danger' : 'primary'"
                    :outlined="(bool) $connectedProvider"
                >
                    {{ $connectedProvider ? 'Disconnect' : 'Connect' }}
                </x-filament::button>
            </div>
        </x-filament::section>
    @endforeach
</div>
