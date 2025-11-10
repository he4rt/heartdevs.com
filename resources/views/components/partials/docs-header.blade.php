@props(['title' => 'Documentação - He4rt Bot API'])

<flux:header class="border-b border-zinc-800 bg-zinc-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center space-x-4">
                <img
                    src="https://avatars.githubusercontent.com/u/47680810?s=200&v=4"
                    alt="He4rt"
                    class="h-10 w-10 rounded-lg"
                />
                <h1 class="text-xl font-bold text-zinc-100">He4rt Bot API</h1>
            </div>

            <flux:navbar class="space-x-6">
                <flux:navbar.item href="/" wire:navigate>Home</flux:navbar.item>
                <flux:navbar.item href="/docs" wire:navigate>Documentação</flux:navbar.item>
                <flux:navbar.item href="https://github.com/he4rt/he4rt-bot-api" target="_blank">
                    GitHub
                </flux:navbar.item>
            </flux:navbar>
        </div>
    </div>
</flux:header>
