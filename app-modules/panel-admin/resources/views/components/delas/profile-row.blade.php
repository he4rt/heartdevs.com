@props(['icon', 'label', 'iconClass' => 'text-gray-400'])

{{-- Uma linha do "Ver perfil": ícone, rótulo pequeno e o valor embaixo. --}}
<div class="flex gap-3">
    <x-filament::icon :icon="$icon" @class(['mt-0.5 size-5 shrink-0', $iconClass]) />

    <div>
        <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</dt>
        <dd class="font-medium text-gray-950 dark:text-white">{{ $slot }}</dd>
    </div>
</div>
