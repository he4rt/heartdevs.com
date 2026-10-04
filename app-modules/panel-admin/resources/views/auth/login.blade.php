<x-filament-panels::page.simple>
    <x-he4rt::auth.social-login />

    <x-he4rt::auth.credential-login>
        {{ $this->content }}
    </x-he4rt::auth.credential-login>
</x-filament-panels::page.simple>
