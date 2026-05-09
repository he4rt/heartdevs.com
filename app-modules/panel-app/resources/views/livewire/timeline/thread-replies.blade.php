<div>
    @if ($replies->isNotEmpty())
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
            <div
                class="px-3 py-2.5 text-xs font-semibold tracking-wide text-gray-500 uppercase sm:px-4 dark:text-gray-400"
            >
                Respostas
            </div>

            <div class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($replies as $reply)
                    <div wire:key="reply-{{ $reply->id }}" class="px-3 py-3 sm:px-4">
                        <div class="flex gap-2 sm:gap-3">
                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-amber-500 text-[10px] font-semibold text-white sm:h-8 sm:w-8 sm:text-xs"
                            >
                                {{ str($reply->user->name)->substr(0, 2)->upper() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-0.5 text-sm">
                                        <span
                                            class="truncate font-semibold text-gray-900 dark:text-white"
                                            >{{ $reply->user->name }}</span
                                        >
                                        @if ($reply->user->username)
                                            <span
                                                class="hidden text-gray-500 sm:inline dark:text-gray-400"
                                                >{{ '@' . $reply->user->username }}</span
                                            >
                                        @endif
                                        <span
                                            class="text-xs text-gray-400 dark:text-gray-500"
                                            >{{ $reply->created_at->diffForHumans(short: true) }}</span
                                        >
                                    </div>
                                    @if (auth()->id() === $reply->user_id)
                                        <button
                                            wire:click="deleteReply('{{ $reply->id }}')"
                                            wire:confirm="Tem certeza que deseja excluir esta resposta?"
                                            class="shrink-0 rounded-full p-1 text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:text-gray-500 dark:hover:bg-red-500/10"
                                        >
                                            <x-heroicon-o-trash class="h-3.5 w-3.5" />
                                        </button>
                                    @endif
                                </div>
                                <div
                                    class="prose prose-sm dark:prose-invert mt-1 max-w-none text-sm break-words text-gray-700 dark:text-gray-300"
                                >
                                    {!!
                                        str($reply->postable->content)
                                            ->markdown()
                                            ->sanitizeHtml()
                                    !!}
                                </div>

                                @if ($reply->postable->getMedia('images')->isNotEmpty())
                                    <div
                                        class="mt-2 grid gap-2 {{ $reply->postable->getMedia('images')->count() > 1 ? 'grid-cols-2' : 'grid-cols-1' }}"
                                    >
                                        @foreach ($reply->postable->getMedia('images') as $media)
                                            <img
                                                src="{{ $media->getUrl() }}"
                                                alt=""
                                                class="max-h-60 w-full rounded-lg border border-gray-200 object-cover dark:border-white/10"
                                                loading="lazy"
                                            />
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($replies->hasMorePages())
                <div x-data x-intersect="$wire.loadMore()" class="flex justify-center py-4">
                    <x-filament::loading-indicator class="h-5 w-5" />
                </div>
            @endif
        </div>
    @else
        <div class="rounded-xl border border-dashed border-gray-200 py-8 text-center dark:border-white/10">
            <x-heroicon-o-chat-bubble-left-right class="mx-auto h-8 w-8 text-gray-300 dark:text-gray-600" />
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nenhuma resposta ainda. Seja o primeiro!</p>
        </div>
    @endif
</div>
