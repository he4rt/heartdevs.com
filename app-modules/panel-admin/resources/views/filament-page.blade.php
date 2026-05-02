<x-filament-panels::page>
    @if (method_exists($this, 'getHeaderWidgets'))
        <x-filament-widgets::widgets :widgets="$this->getHeaderWidgets()" :columns="$this->getHeaderWidgetsColumns()" />
    @endif
</x-filament-panels::page>
