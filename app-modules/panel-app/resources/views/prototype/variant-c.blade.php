{{-- PROTOTYPE — Variant C: "Timeline Rail"
    Vertical timeline rail on the left connecting entries chronologically.
    Posts branch off to the right. Moderation events are marked with special
    color-coded dots on the rail. Emphasis on temporal flow.
    Layout: max-w-3xl, timeline aesthetic with connecting line.
    Vibe: git log / VS Code timeline — chronological, visual hierarchy via rail. --}}

<div class="mx-auto max-w-3xl">
    <div class="relative">
        {{-- Vertical rail --}}
        <div class="absolute top-0 bottom-0 left-5 w-px bg-gray-200 dark:bg-white/10"></div>

        @foreach ($mockFeed as $i => $item)
            <div class="relative flex gap-5 {{ $i > 0 ? 'mt-1' : '' }}">
                {{-- Rail dot --}}
                <div class="relative z-10 flex w-10 shrink-0 justify-center pt-4">
                    @if ($item['type'] === 'moderation_event')
                        <div
                            class="bg-danger-500 ring-danger-500/20 dark:ring-danger-500/10 flex h-4 w-4 items-center justify-center rounded-full ring-4"
                        >
                            <x-heroicon-s-shield-exclamation class="h-2.5 w-2.5 text-white" />
                        </div>
                    @elseif ($item['pinned'] ?? false)
                        <div
                            class="bg-warning-500 ring-warning-500/20 dark:ring-warning-500/10 flex h-4 w-4 items-center justify-center rounded-full ring-4"
                        >
                            <x-heroicon-s-map-pin class="h-2.5 w-2.5 text-white" />
                        </div>
                    @else
                        <div
                            class="bg-primary-500 ring-primary-500/20 dark:ring-primary-500/10 h-3 w-3 rounded-full ring-4"
                        ></div>
                    @endif
                </div>

                {{-- Content --}}
                <div class="flex-1 pb-6">
                    @if ($item['type'] === 'post_entry')
                        <div
                            class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 overflow-hidden
                            {{ ($item['pinned'] ?? false) ? 'ring-1 ring-warning-500/30' : '' }}"
                        >
                            {{-- Header --}}
                            <div class="flex items-center gap-3 px-4 pt-3 pb-1.5">
                                <div
                                    class="from-primary-500 to-warning-500 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br text-xs font-bold text-white"
                                >
                                    {{ $item['user']['initials'] }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5 text-sm">
                                        <span
                                            class="font-semibold text-gray-900 dark:text-white"
                                            >{{ $item['user']['name'] }}</span
                                        >
                                        @if ($item['user']['badge'])
                                            <span
                                                class="text-[8px] font-mono font-bold uppercase tracking-wider px-1 py-px rounded
                                                {{ $item['user']['badge'] === 'founder' ? 'bg-warning-500/10 text-warning-600 dark:text-warning-400 border border-warning-500/20' : 'bg-primary-500/10 text-primary-600 dark:text-primary-400 border border-primary-500/20' }}"
                                            >
                                                {{ strtoupper($item['user']['badge']) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                                        <span>@{{ $item['user']['username'] }}</span>
                                        <span>·</span>
                                        <span>{{ $item['time'] }}</span>
                                        @if ($item['pinned'] ?? false)
                                            <span class="text-warning-500 flex items-center gap-0.5">
                                                · <x-heroicon-s-map-pin class="h-3 w-3" /> pinned
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="px-4 pb-2">
                                <div
                                    class="prose prose-sm dark:prose-invert max-w-none leading-relaxed text-gray-700 dark:text-gray-300"
                                >
                                    {!! str($item['content'])->markdown()->sanitizeHtml() !!}
                                </div>
                                @if (!empty($item['images']))
                                    <div
                                        class="from-primary-500/15 to-warning-500/15 mt-3 flex aspect-[16/8] items-center justify-center rounded-lg border border-gray-200 bg-gradient-to-br via-transparent dark:border-white/10"
                                    >
                                        <span class="font-mono text-xs text-gray-400">[image]</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Stats bar --}}
                            <div
                                class="flex items-center gap-4 border-t border-gray-100 px-4 py-2 text-xs text-gray-400 dark:border-white/5 dark:text-gray-500"
                            >
                                <span class="hover:text-primary-500 flex cursor-pointer items-center gap-1 transition">
                                    <x-heroicon-o-chat-bubble-left-right class="h-3.5 w-3.5" />
                                    {{ $item['replies_count'] }} respostas
                                </span>
                                <span class="hover:text-warning-500 flex cursor-pointer items-center gap-1 transition">
                                    <x-heroicon-o-face-smile class="h-3.5 w-3.5" />
                                    {{ $item['reactions_count'] }}
                                </span>
                                <span class="ml-auto flex items-center gap-1">
                                    <x-heroicon-o-eye class="h-3.5 w-3.5" />
                                    {{ number_format($item['views']) }}
                                </span>
                            </div>

                            {{-- Threaded replies --}}
                            @if (!empty($item['replies']))
                                <div class="border-t border-gray-100 dark:border-white/5">
                                    @foreach (array_slice($item['replies'], 0, 2) as $reply)
                                        <div
                                            class="flex gap-2.5 border-b border-gray-50 px-4 py-2.5 last:border-0 dark:border-white/[0.03]"
                                        >
                                            <div
                                                class="from-primary-400 to-warning-400 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gradient-to-br text-[9px] font-bold text-white"
                                            >
                                                {{ $reply['user']['initials'] }}
                                            </div>
                                            <div class="min-w-0 flex-1 text-xs">
                                                <span
                                                    class="font-semibold text-gray-800 dark:text-gray-200"
                                                    >{{ $reply['user']['name'] }}</span
                                                >
                                                <span class="ml-1 text-gray-400">{{ $reply['time'] }}</span>
                                                <p class="mt-0.5 leading-snug text-gray-600 dark:text-gray-400">{!! str($reply['content'])->markdown()->sanitizeHtml() !!}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                    @elseif ($item['type'] === 'moderation_event')
                        {{-- Moderation: compact, no card, hangs directly off the rail --}}
                        <div
                            class="border-danger-200 dark:border-danger-500/20 bg-danger-50/30 dark:bg-danger-500/5 rounded-lg border px-4 py-3"
                        >
                            <div class="flex items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2 text-sm">
                                        <span
                                            class="bg-danger-500/10 text-danger-600 dark:text-danger-400 rounded px-1.5 py-0.5 font-mono text-[9px] font-bold tracking-wider uppercase"
                                        >
                                            {{ $item['mod_type'] }}
                                        </span>
                                        <span
                                            class="font-semibold text-gray-900 dark:text-white"
                                            >{{ $item['subject'] }}</span
                                        >
                                        @if ($item['moderator_visible'])
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                por <span class="font-medium">{{ $item['moderator']['name'] }}</span>
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1.5 text-xs text-gray-600 dark:text-gray-400">{{ $item['reason'] }}</p>
                                    <div
                                        class="mt-2 flex items-center gap-3 font-mono text-[10px] text-gray-400 dark:text-gray-500"
                                    >
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-o-flag class="h-3 w-3" />
                                            {{ $item['reports_count'] }} denúncias
                                        </span>
                                        <span>{{ $item['violation'] }}</span>
                                        <span>{{ $item['time'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- End cap --}}
        <div class="relative flex gap-5">
            <div class="relative z-10 flex w-10 shrink-0 justify-center">
                <div class="h-2 w-2 rounded-full bg-gray-300 dark:bg-gray-600"></div>
            </div>
            <div class="pb-4 font-mono text-xs text-gray-400">— fim do feed —</div>
        </div>
    </div>
</div>
