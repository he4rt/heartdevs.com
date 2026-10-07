<x-filament-panels::page>
    <x-filament::tabs :label="__('panel-app::delas.moderation.title')">
        @foreach ($this->tabs() as $key => $tab)
            <x-filament::tabs.item
                :active="$this->tab === $key"
                :icon="$tab['icon']"
                :badge="$tab['badge'] ?: null"
                :badge-color="$key === 'pending' ? 'primary' : 'gray'"
                wire:click="$set('tab', '{{ $key }}')"
            >
                {{ $tab['label'] }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    {{ $this->table }}
</x-filament-panels::page>
