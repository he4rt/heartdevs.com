<x-filament-panels::page.simple>
    <div class="grid gap-3">
        <a
            href="{{ route('oauth.redirect', ['tenant' => request()->getHost(), 'panel' => 'app', 'provider' => 'discord']) }}"
            class="flex items-center justify-center gap-2 rounded-lg bg-[#5865F2] px-4 py-3 text-sm font-medium text-white transition hover:bg-[#4752C4]"
        >
            @svg ('fab-discord', 'h-5 w-5')
            Continuar com Discord
        </a>

        <a
            href="{{ route('oauth.redirect', ['tenant' => request()->getHost(), 'panel' => 'app', 'provider' => 'github']) }}"
            class="flex items-center justify-center gap-2 rounded-lg bg-[#24292f] px-4 py-3 text-sm font-medium text-white transition hover:bg-[#1b1f23] dark:bg-[#f0f0f0] dark:text-[#24292f] dark:hover:bg-[#d4d4d4]"
        >
            @svg ('fab-github', 'h-5 w-5')
            Continuar com GitHub
        </a>
    </div>

    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
        </div>
        <div class="relative flex justify-center text-xs uppercase">
            <span class="bg-white px-2 text-gray-500 dark:bg-gray-900 dark:text-gray-400">ou</span>
        </div>
    </div>

    {{ $this->content }}
</x-filament-panels::page.simple>
