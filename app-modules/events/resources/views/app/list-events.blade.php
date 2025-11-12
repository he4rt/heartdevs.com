<x-filament-panels::page>
    @php
        $events = $this->getTableRecords();
    @endphp

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($events as $event)
            <div
                class="bg-card text-card-foreground h-full rounded-lg border shadow-sm transition-shadow hover:shadow-lg"
            >
                <div class="flex flex-col p-6 pb-3">
                    <div class="flex items-start justify-between gap-2">
                        {{-- CardTitle: text-base leading-tight --}}
                        <h3 class="text-base leading-tight font-semibold tracking-tight">
                            {{ $event->title }}
                        </h3>

                        {{-- Badge Status: text-xs, shrink-0 --}}
                        <span
                            class="focus:ring-ring inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors focus:ring-2 focus:ring-offset-2 focus:outline-none"
                        >
                            {{ $event->end_at < now() ? 'Past' : 'Upcoming' }}
                        </span>
                    </div>

                    <span
                        class="focus:ring-ring border-input bg-background hover:bg-accent hover:text-accent-foreground mt-2 inline-flex w-fit items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:ring-2 focus:ring-offset-2 focus:outline-none"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="12"
                            height="12"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="mr-1 h-3 w-3"
                        >
                            <path
                                d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2 2 0 0 0 2.828 0l7.172-7.172a2 2 0 0 0 0-2.828z"
                            />
                            <path d="M9 9h.01" />
                        </svg>
                        {{ $event->event_type->getLabel() }}
                    </span>
                </div>

                {{-- CardContent: space-y-3 --}}
                <div class="space-y-3 p-6 pt-0">
                    <p class="text-muted-foreground text-xs leading-relaxed">{{ $event->description }}</p>

                    {{-- Informações Detalhadas: space-y-2 text-xs text-muted-foreground --}}
                    <div class="text-muted-foreground space-y-2 text-xs">
                        {{-- Date --}}
                        <div class="flex items-center gap-2">
                            {{-- Icon Calendar --}}
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="h-3.5 w-3.5 shrink-0"
                            >
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
                                <line x1="16" x2="16" y1="2" y2="6" />
                                <line x1="8" x2="8" y1="2" y2="6" />
                                <line x1="3" x2="21" y1="10" y2="10" />
                            </svg>
                            <span>
                                {{ \Carbon\Carbon::parse($event->event_at)->format('d/m/Y') }}
                            </span>
                        </div>

                        {{-- Time --}}
                        <div class="flex items-center gap-2">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="h-3.5 w-3.5 shrink-0"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                            <span>
                                {{ \Carbon\Carbon::parse($event->start_at)->format('H:i:s') }} -
                                {{ \Carbon\Carbon::parse($event->end_at)->format('H:i:s') }}
                            </span>
                        </div>

                        {{-- Location --}}
                        <div class="flex items-center gap-2">
                            {{-- Icon MapPin --}}
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="h-3.5 w-3.5 shrink-0"
                            >
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg>
                            <span>{{ $event->location }}</span>
                        </div>

                        {{-- Participants --}}
                        <div class="flex items-center gap-2">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="h-3.5 w-3.5 shrink-0"
                            >
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                            <span>{{ $event->attendees_count }} / {{ $event->max_attendees }} participants</span>
                        </div>
                    </div>
                    @if ($event->attendees()->first()->pivot->status === \He4rt\Events\Enums\AttendingStatusEnum::Attending)
                        <button
                            class="focus-visible:ring-ring bg-primary text-primary-foreground hover:bg-primary/90 mt-2 inline-flex h-9 w-full items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                        >
                            Join
                        </button>
                    @elseif ($event->attendees()->first()->pivot->status === \He4rt\Events\Enums\AttendingStatusEnum::Waitlist)
                        <button
                            class="focus-visible:ring-ring bg-primary text-primary-foreground hover:bg-primary/90 mt-2 inline-flex h-9 w-full items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                        >
                            Join Waitlist
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
