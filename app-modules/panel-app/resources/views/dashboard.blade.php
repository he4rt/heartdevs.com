<x-filament-panels::page>
    {{-- OAuth callbacks land here; persist the provider for the next login page render. --}}
    @if (in_array(request()->query('oauth_provider'), ['discord', 'github', 'twitch'], strict: true))
        <div
            class="hidden"
            x-data="{}"
            x-load-js="[@js(\Filament\Support\Facades\FilamentAsset::getScriptSrc('last-auth-provider', package: 'he4rt/panel-app'))]"
        ></div>
    @endif

    <div class="mx-auto w-full max-w-4xl">
        <livewire:timeline-feed />
    </div>
</x-filament-panels::page>
