<article class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
    <x-panel-app::timeline.header
        :user="$timeline->user"
        :pinned="$timeline->pinned"
        :created-at="$timeline->created_at"
    />

    @if ($timeline->postable_type === 'post_entry')
        <x-panel-app::timeline.post-entry :content="$timeline->postable" />
    @endif

    <x-panel-app::timeline.engagement :timeline="$timeline" :is-owner="auth()->id() === $timeline->user_id" />

    @if ($showReplies && $timeline->children_count > 0)
        <div class="space-y-3 border-t border-gray-200 px-3 py-3 sm:px-4 dark:border-white/10">
            @foreach ($timeline->children->take(3) as $reply)
                @continue (!$reply->postable)
                <div class="flex gap-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-amber-500 text-xs font-semibold text-white"
                    >
                        {{ str($reply->user->name)->substr(0, 2)->upper() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $reply->user->name }}</span>
                            <span
                                class="text-xs text-gray-500"
                                >{{ $reply->created_at->diffForHumans(short: true) }}</span
                            >
                        </div>
                        <div
                            class="prose prose-sm dark:prose-invert mt-0.5 max-w-none text-sm text-gray-700 dark:text-gray-300"
                        >
                            {!!
                                str($reply->postable->content)
                                    ->markdown()
                                    ->sanitizeHtml()
                            !!}
                        </div>
                    </div>
                </div>
            @endforeach

            @if ($timeline->children_count > 3)
                <a
                    href="{{ \He4rt\PanelApp\Pages\ThreadPage::getUrl(['record' => $timeline->id]) }}"
                    class="text-primary-600 hover:text-primary-500 block text-center text-sm font-medium transition"
                >
                    Ver todas as {{ $timeline->children_count }} respostas &rarr;
                </a>
            @endif
        </div>
    @endif
</article>
