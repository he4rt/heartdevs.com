@php
    $currentTenant = \Filament\Facades\Filament::getTenant();
    $tenants = \Filament\Facades\Filament::getUserTenants(\Filament\Facades\Filament::auth()->user());
    $panelId = \Filament\Facades\Filament::getCurrentPanel()->getId();
    
    $providers = \He4rt\Identity\ExternalIdentity\Enums\IdentityProvider::supportedProviders();
    $activeProviderValue = session('active_provider', \He4rt\Identity\ExternalIdentity\Enums\IdentityProvider::Discord->value);
    $currentProvider = \He4rt\Identity\ExternalIdentity\Enums\IdentityProvider::tryFrom($activeProviderValue) ?? $providers[1];
@endphp

<div class="fi-topbar-nav-pickers flex items-center gap-12 px-4">
    {{-- Tenant Picker --}}
    <x-filament::dropdown placement="bottom-start" teleport>
        <x-slot name="trigger">
            <button class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-lg hover:bg-slate-50 transition dark:hover:bg-white/5 border border-transparent hover:border-slate-200/70 dark:hover:border-white/10">
                <div class="w-7 h-7 rounded-md flex items-center justify-center text-white font-bold text-xs" style="background: #7c3aed">
                    <x-heroicon-m-building-office-2 class="w-3.5 h-3.5" />
                </div>
                <div class="text-left">
                    <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold leading-none">Tenant</div>
                    <div class="text-[12px] font-semibold text-slate-900 dark:text-white leading-none mt-0.5">{{ $currentTenant->name }}</div>
                </div>
            </button>
        </x-slot>

        <x-filament::dropdown.list class="w-96 !p-1">
            <div class="px-3 py-2 text-[10px] uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-100 dark:border-white/5 mb-1">Trocar escopo de tenant</div>
            
            @foreach ($tenants as $tenant)
                @php
                    $isActive = $tenant->id === $currentTenant->id;
                @endphp
                <x-filament::dropdown.list.item
                    :href="\Filament\Facades\Filament::getPanel($panelId)->getUrl($tenant)"
                    tag="a"
                    :active="$isActive"
                    class="rounded-md px-3"
                >
                    <div class="flex items-center gap-3 w-full">
                        <div class="w-8 h-8 rounded-md flex items-center justify-center text-white font-bold text-xs shrink-0" style="background: #7c3aed">
                            <x-heroicon-m-building-office-2 class="w-4 h-4" />
                        </div>
                        <div class="flex flex-col min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-[13px] font-semibold text-slate-900 dark:text-white leading-tight truncate">
                                    {{ $tenant->name }}
                                </span>
                                @if ($isActive)
                                    <span class="shrink-0 text-[9px] px-1.5 py-0.5 bg-violet-600 text-white rounded font-bold uppercase tracking-tighter">Ativo</span>
                                @endif
                            </div>
                            <span class="text-[11px] text-slate-500 leading-tight mt-0.5 truncate">{{ $tenant->domain ?? $tenant->slug }}</span>
                        </div>
                    </div>
                </x-filament::dropdown.list.item>
            @endforeach

            <div class="border-t border-slate-100 dark:border-white/5 my-1"></div>

            <x-filament::dropdown.list.item
                :href="\Filament\Facades\Filament::getTenantProfileUrl()"
                icon="heroicon-m-cog-6-tooth"
                tag="a"
                class="rounded-md"
            >
                <span class="text-[13px] font-medium text-slate-700 dark:text-slate-300">Gerenciar Tenant</span>
            </x-filament::dropdown.list.item>
        </x-filament::dropdown.list>
    </x-filament::dropdown>

    <span class="text-slate-300 text-sm mx-1">·</span>

    {{-- Provider Picker --}}
    <x-filament::dropdown placement="bottom-start" teleport>
        <x-slot name="trigger">
            <button class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-lg hover:bg-slate-50 transition dark:hover:bg-white/5 border border-transparent hover:border-slate-200/70 dark:hover:border-white/10">
                <div class="w-7 h-7 rounded-md flex items-center justify-center text-white" style="background: {{ is_array($currentProvider->getColor()) ? ($currentProvider->getColor()[0] ?? '#64748b') : $currentProvider->getColor() }}">
                    @svg($currentProvider->getIcon(), 'w-3.5 h-3.5')
                </div>
                <div class="text-left">
                    <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold leading-none">Provider</div>
                    <div class="text-[12px] font-semibold text-slate-900 dark:text-white leading-none mt-0.5">{{ $currentProvider->getLabel() }}</div>
                </div>
            </button>
        </x-slot>

        <x-filament::dropdown.list class="w-64">
            @foreach ($providers as $p)
                @php
                    $isActive = $p->value === $currentProvider->value;
                @endphp
                <x-filament::dropdown.list.item
                    :href="request()->fullUrlWithQuery(['provider' => $p->value])"
                    tag="a"
                    :active="$isActive"
                >
                    <div class="flex items-center gap-3 w-full">
                        <div class="w-7 h-7 rounded-md flex items-center justify-center text-white shrink-0" style="background: {{ is_array($p->getColor()) ? ($p->getColor()[0] ?? '#64748b') : $p->getColor() }}">
                            @svg($p->getIcon(), 'w-3.5 h-3.5')
                        </div>
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <span class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $p->getLabel() }}</span>
                            @if ($isActive)
                                <span class="shrink-0 text-[9px] px-2 py-0.5 bg-violet-600 text-white rounded font-bold uppercase">Ativo</span>
                            @endif
                        </div>
                    </div>
                </x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>
