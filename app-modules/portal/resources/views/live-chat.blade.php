<section class="border-outline-low bg-elevation-01dp flex h-[32rem] flex-col rounded-lg border">
    <header class="border-outline-low border-b px-4 py-3">
        <h2 class="text-text-high text-sm font-semibold">Chat da live</h2>
    </header>

    <div wire:ignore data-chat-list class="flex-1 space-y-3 overflow-y-auto p-4">
        @foreach ($history as $message)
            <div data-chat-message data-message-id="{{ $message['id'] }}" class="flex items-start gap-2">
                <img src="{{ $message['authorAvatarUrl'] }}" alt="" class="h-6 w-6 rounded-full" />
                <p class="text-text-medium text-sm break-words">
                    <span class="text-text-high font-semibold">{{ $message['authorUsername'] }}</span>
                    {{ $message['content'] }}
                </p>
            </div>
        @endforeach
    </div>

    @auth
        <form wire:submit="send" class="border-outline-low flex items-center gap-2 border-t p-3">
            <input
                wire:model="draft"
                type="text"
                maxlength="500"
                placeholder="Mande sua mensagem"
                class="bg-elevation-02dp text-text-high w-full rounded-md px-3 py-2 text-sm"
            />
            <button type="submit" class="bg-primary rounded-md px-3 py-2 text-sm font-semibold text-white">Enviar</button>
        </form>
        @error('draft')
            <p class="px-3 pb-3 text-xs text-red-400">{{ $message }}</p>
        @enderror
    @else
        <p class="border-outline-low text-text-medium border-t p-4 text-center text-sm">
            <a href="{{ route('filament.app.auth.login') }}" class="text-primary underline">Entre para participar do chat</a>
        </p>
    @endauth
</section>
