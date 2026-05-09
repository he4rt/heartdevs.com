{{-- PROTOTYPE — Variant B: "Dense Activity Stream"
    No cards — just a continuous stream with subtle dividers. Small avatars,
    tight spacing, emphasis on information density. Replies inline.
    Layout: full-width content area, no max-width constraint.
    Vibe: GitHub Activity / HN — dense, scannable, efficient. --}}

<div class="divide-y divide-gray-100 dark:divide-white/5">
    @foreach ($mockFeed as $item)
        @if ($item['type'] === 'post_entry')
            <div class="group px-2 py-3 transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                <div class="flex gap-2.5">
                    {{-- Avatar --}}
                    <div
                        class="from-primary-500 to-warning-500 mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-gradient-to-br text-[10px] font-bold text-white"
                    >
                        {{ $item['user']['initials'] }}
                    </div>

                    <div class="min-w-0 flex-1">
                        {{-- Meta line --}}
                        <div class="flex items-center gap-1.5 text-xs leading-tight">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $item['user']['name'] }}</span>
                            @if ($item['user']['badge'])
                                <span
                                    class="text-[8px] font-mono font-bold uppercase px-1 py-px rounded
                                    {{ $item['user']['badge'] === 'founder' ? 'bg-warning-500/10 text-warning-600 dark:text-warning-400' : 'bg-primary-500/10 text-primary-600 dark:text-primary-400' }}"
                                >
                                    {{ strtoupper($item['user']['badge']) }}
                                </span>
                            @endif
                            <span class="text-gray-400 dark:text-gray-600">@{{ $item['user']['username'] }}</span>
                            <span class="text-gray-300 dark:text-gray-700">·</span>
                            <span class="text-gray-400 dark:text-gray-500">{{ $item['time'] }}</span>
                            @if ($item['pinned'])
                                <x-heroicon-s-map-pin class="text-warning-500 ml-1 h-3 w-3" />
                            @endif
                            <div
                                class="ml-auto flex items-center gap-2 text-gray-400 opacity-0 transition-opacity group-hover:opacity-100 dark:text-gray-500"
                            >
                                <span class="flex items-center gap-0.5"
                                    ><x-heroicon-o-chat-bubble-left class="h-3 w-3" />
                                    {{ $item['replies_count'] }}</span
                                >
                                <span class="flex items-center gap-0.5"
                                    ><x-heroicon-o-face-smile class="h-3 w-3" /> {{ $item['reactions_count'] }}</span
                                >
                                <span class="flex items-center gap-0.5"
                                    ><x-heroicon-o-eye class="h-3 w-3" /> {{ number_format($item['views']) }}</span
                                >
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="mt-1 text-sm leading-snug text-gray-700 dark:text-gray-300">
                            {!! str($item['content'])->markdown()->sanitizeHtml() !!}
                        </div>

                        @if (!empty($item['images']))
                            <div
                                class="from-primary-500/10 to-warning-500/10 mt-2 flex h-32 items-center justify-center rounded border border-gray-200 bg-gradient-to-r dark:border-white/10"
                            >
                                <span class="font-mono text-[10px] text-gray-400">[image]</span>
                            </div>
                        @endif

                        {{-- Inline replies --}}
                        @if (!empty($item['replies']))
                            <div class="mt-2 space-y-1.5 border-l-2 border-gray-200 pl-3 dark:border-white/10">
                                @foreach (array_slice($item['replies'], 0, 2) as $reply)
                                    <div class="text-xs">
                                        <span
                                            class="font-semibold text-gray-700 dark:text-gray-300"
                                            >{{ $reply['user']['name'] }}</span
                                        >
                                        <span class="text-gray-400 dark:text-gray-600">{{ $reply['time'] }}</span>
                                        <span
                                            class="ml-1 text-gray-500 dark:text-gray-400"
                                            >{!! str($reply['content'])->markdown()->sanitizeHtml() !!}</span
                                        >
                                    </div>
                                @endforeach
                                @if ($item['replies_count'] > 2)
                                    <span class="text-primary-500 cursor-pointer font-mono text-[10px] hover:underline">
                                        +{{ $item['replies_count'] - 2 }} more
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        @elseif ($item['type'] === 'moderation_event')
            <div class="flex items-start gap-2.5 px-2 py-2.5">
                <div
                    class="bg-danger-100 dark:bg-danger-500/10 flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
                >
                    <x-heroicon-s-shield-exclamation class="text-danger-500 h-3.5 w-3.5" />
                </div>
                <div class="min-w-0 flex-1 text-xs leading-snug">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span
                            class="text-danger-500 text-[9px] font-bold tracking-wider uppercase"
                            >{{ $item['mod_type'] }}</span
                        >
                        <span class="text-gray-600 dark:text-gray-400">
                            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $item['subject'] }}</span>
                            — {{ $item['reason'] }}
                        </span>
                        @if ($item['moderator_visible'])
                            <span class="text-gray-400 dark:text-gray-500">por {{ $item['moderator']['name'] }}</span>
                        @endif
                        <span class="ml-auto text-gray-300 dark:text-gray-600"
                            >{{ $item['reports_count'] }} reports · {{ $item['time'] }}</span
                        >
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
