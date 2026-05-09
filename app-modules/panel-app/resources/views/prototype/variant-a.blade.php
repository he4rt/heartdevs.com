{{-- PROTOTYPE — Variant A: "Card Feed"
    Standard social media cards. Each post is a bordered card with generous padding,
    avatar circle, markdown content, and engagement footer row.
    Layout: single column, max-w-2xl centered.
    Vibe: Twitter/X — spacious, card-based, familiar. --}}

<div class="mx-auto max-w-2xl space-y-4">
    @foreach ($mockFeed as $item)
        @if ($item['type'] === 'post_entry')
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/5"
            >
                {{-- Header --}}
                <div class="flex items-center gap-3 px-5 pt-4 pb-2">
                    <div
                        class="from-primary-500 to-warning-500 flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br text-sm font-bold text-white"
                    >
                        {{ $item['user']['initials'] }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="text-sm font-semibold text-gray-900 dark:text-white"
                                >{{ $item['user']['name'] }}</span
                            >
                            @if ($item['user']['badge'])
                                <span
                                    class="text-[9px] font-mono font-bold uppercase tracking-wider px-1.5 py-0.5 rounded
                                    {{ $item['user']['badge'] === 'founder' ? 'bg-warning-500/10 text-warning-500 border border-warning-500/30' : 'bg-primary-500/10 text-primary-500 border border-primary-500/30' }}"
                                >
                                    {{ strtoupper($item['user']['badge']) }}
                                </span>
                            @endif
                            <span
                                class="text-sm text-gray-400 dark:text-gray-500"
                                >{{ '@' . $item['user']['username'] }}</span
                            >
                            <span class="text-xs text-gray-400 dark:text-gray-600">· {{ $item['time'] }}</span>
                        </div>
                    </div>
                    @if ($item['pinned'])
                        <div class="text-warning-500 flex items-center gap-1 text-xs">
                            <x-heroicon-s-map-pin class="h-3.5 w-3.5" />
                            <span class="font-medium">Fixado</span>
                        </div>
                    @endif
                </div>

                {{-- Content --}}
                <div class="px-5 pb-3">
                    <div
                        class="prose prose-sm dark:prose-invert max-w-none leading-relaxed text-gray-800 dark:text-gray-200"
                    >
                        {!! str($item['content'])->markdown()->sanitizeHtml() !!}
                    </div>
                    @if (!empty($item['images']))
                        <div
                            class="from-primary-500/20 to-warning-500/20 mt-3 flex aspect-video items-center justify-center rounded-lg border border-gray-200 bg-gradient-to-br via-gray-500/10 dark:border-white/10"
                        >
                            <span class="font-mono text-xs text-gray-400">[image placeholder]</span>
                        </div>
                    @endif
                </div>

                {{-- Engagement --}}
                <div
                    class="flex items-center justify-between border-t border-gray-100 px-5 py-2.5 text-sm text-gray-400 dark:border-white/5 dark:text-gray-500"
                >
                    <div class="flex items-center gap-5">
                        <span class="hover:text-primary-500 flex cursor-pointer items-center gap-1.5 transition">
                            <x-heroicon-o-chat-bubble-left-right class="h-4 w-4" />
                            {{ $item['replies_count'] }}
                        </span>
                        <span class="hover:text-warning-500 flex cursor-pointer items-center gap-1.5 transition">
                            <x-heroicon-o-face-smile class="h-4 w-4" />
                            {{ $item['reactions_count'] }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <x-heroicon-o-eye class="h-4 w-4" />
                            {{ number_format($item['views']) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-bookmark class="hover:text-primary-500 h-4 w-4 cursor-pointer transition" />
                        <x-heroicon-o-share class="hover:text-primary-500 h-4 w-4 cursor-pointer transition" />
                    </div>
                </div>

                {{-- Replies preview --}}
                @if (!empty($item['replies']))
                    <div
                        class="space-y-3 border-t border-gray-100 bg-gray-50/50 px-5 py-3 dark:border-white/5 dark:bg-white/[0.02]"
                    >
                        @foreach (array_slice($item['replies'], 0, 2) as $reply)
                            <div class="flex gap-3">
                                <div
                                    class="from-primary-400 to-warning-400 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br text-[10px] font-bold text-white"
                                >
                                    {{ $reply['user']['initials'] }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 text-xs">
                                        <span
                                            class="font-semibold text-gray-800 dark:text-gray-200"
                                            >{{ $reply['user']['name'] }}</span
                                        >
                                        <span class="text-gray-400">{{ $reply['time'] }}</span>
                                    </div>
                                    <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">{!! str($reply['content'])->markdown()->sanitizeHtml() !!}</p>
                                </div>
                            </div>
                        @endforeach
                        @if ($item['replies_count'] > 2)
                            <p class="text-primary-500 cursor-pointer text-xs font-medium hover:underline">Ver {{ $item['replies_count'] - 2 }} respostas a mais →</p>
                        @endif
                    </div>
                @endif
            </div>

        @elseif ($item['type'] === 'moderation_event')
            <div
                class="border-danger-300 dark:border-danger-500/30 ring-danger-500/10 overflow-hidden rounded-xl border bg-white shadow-sm ring-1 dark:bg-white/5"
            >
                {{-- Accent bar --}}
                <div class="from-danger-500 to-danger-400 h-1 bg-gradient-to-r"></div>

                {{-- Header --}}
                <div class="flex items-center gap-3 px-5 pt-4 pb-2">
                    <div
                        class="bg-danger-100 dark:bg-danger-500/15 ring-danger-500/20 flex h-11 w-11 shrink-0 items-center justify-center rounded-full ring-2"
                    >
                        <x-heroicon-s-shield-exclamation class="text-danger-500 h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Moderação</span>
                            <span
                                class="bg-danger-500 rounded-full px-2 py-0.5 text-[9px] font-bold tracking-wider text-white uppercase"
                            >
                                {{ $item['mod_type'] }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-600">· {{ $item['time'] }}</span>
                        </div>
                        @if ($item['moderator_visible'])
                            <span class="text-xs text-gray-400 dark:text-gray-500"
                                >por {{ $item['moderator']['name'] }}</span
                            >
                        @endif
                    </div>
                </div>

                {{-- Content — hero block --}}
                <div
                    class="border-danger-200 dark:border-danger-500/20 from-danger-50 to-danger-50/50 dark:from-danger-950/40 dark:to-danger-950/20 mx-5 mb-4 overflow-hidden rounded-xl border bg-gradient-to-br via-white dark:via-zinc-900"
                >
                    <div class="px-5 py-5 text-center">
                        <div
                            class="bg-danger-100 dark:bg-danger-500/15 mb-3 inline-flex h-14 w-14 items-center justify-center rounded-full"
                        >
                            <x-heroicon-s-no-symbol class="text-danger-500 h-7 w-7" />
                        </div>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $item['subject'] }}</p>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">foi {{ $item['mod_type'] === 'Ban' ? 'banido permanentemente' : 'removido' }} da comunidade</p>
                    </div>

                    <div
                        class="border-danger-100 dark:border-danger-500/10 bg-danger-50/50 dark:bg-danger-500/5 border-t px-5 py-3"
                    >
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="text-danger-600 dark:text-danger-400 font-semibold">Motivo:</span>
                            {{ $item['reason'] }}
                        </p>
                    </div>

                    <div
                        class="border-danger-100 dark:border-danger-500/10 flex items-center justify-center gap-5 border-t px-5 py-2.5 text-xs text-gray-400 dark:text-gray-500"
                    >
                        <span class="flex items-center gap-1.5">
                            <x-heroicon-o-flag class="text-danger-400 h-3.5 w-3.5" />
                            {{ $item['reports_count'] }} denúncias
                        </span>
                        <span class="flex items-center gap-1.5">
                            <x-heroicon-o-exclamation-triangle class="text-warning-500 h-3.5 w-3.5" />
                            {{ $item['violation'] }}
                        </span>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <div class="py-8 text-center font-mono text-xs text-gray-400">— fim do feed —</div>
</div>
