@if(app()->isLocal())
    <div class="my-6 flex items-center gap-3">
        <hr class="flex-1 border-t border-zinc-800">
        <span class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('he4rt::auth.or') }}</span>
        <hr class="flex-1 border-t border-zinc-800">
    </div>

    {{ $slot }}
@endif
