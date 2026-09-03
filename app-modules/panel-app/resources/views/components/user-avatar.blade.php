@props (['user' => null, 'size' => 'md'])

@use('He4rt\Profile\Support\ProfileInitials')

@php
    $displayName = $user?->name ?? 'Usuário removido';
    $avatarUrl = $user?->getFirstMediaUrl('avatar') ?: null;
    $initials = ProfileInitials::for($displayName, $user?->username);

    $sizeClasses = match ($size) {
        'lg' => 'h-9 w-9 sm:h-10 sm:w-10',
        'sm' => 'h-7 w-7 sm:h-8 sm:w-8',
        default => 'h-8 w-8',
    };

    $initialsClasses = match ($size) {
        'lg' => 'text-xs sm:text-sm',
        'sm' => 'text-[10px] sm:text-xs',
        default => 'text-xs',
    };
@endphp

@if ($avatarUrl)
    <img
        src="{{ $avatarUrl }}"
        alt="{{ $displayName }}"
        {{ $attributes->class(['shrink-0 rounded-full object-cover', $sizeClasses]) }}
    />
@else
    <div
        {{
            $attributes->class([
                'flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-amber-500 font-semibold text-white',
                $sizeClasses,
                $initialsClasses,
            ])
        }}
    >
        {{ $initials }}
    </div>
@endif
