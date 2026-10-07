@props(['size' => 'sm'])

{{-- Tag He4rt Delas: rosa claro com texto e ícone no tom 900 (contraste 4,81:1). --}}
<span
    {{
        $attributes->class([
            'bg-delas-primary text-delas-900 inline-flex items-center gap-1 rounded-full font-bold shadow-sm',
            'px-3 py-1 text-sm' => $size === 'lg',
            'px-2.5 py-0.5 text-xs' => $size !== 'lg',
        ])
    }}
>
    <x-panel-app::delas.logo @class(['w-auto', 'h-3.5' => $size === 'lg', 'h-3' => $size !== 'lg']) />
    He4rt Delas
</span>
