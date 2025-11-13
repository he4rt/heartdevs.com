<x-filament-panels::page>
    @php
        $events = $this->getTableRecords();
    @endphp

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($events as $event)
            <x-filament::section
                :wire:key="$event->getKey()"
                :has-content-el="false"
                :icon="$event->event_type->getIcon()"
            >
                <x-slot name="heading">
                    <div class="flex justify-between">
                        <span>{{ $event->event_type->getLabel() }}</span>
                        <x-filament::badge>
                            {{ $event->end_at < now() ? 'Past' : 'Upcoming' }}
                        </x-filament::badge>
                    </div>
                </x-slot>
                <div class="fi-section-content flex flex-col justify-between">
                    <div>
                        <h3 class="mb-2 text-base leading-tight font-semibold tracking-tight">
                            {{ $event->title }}
                        </h3>
                        <p class="text-muted-foreground text-xs leading-relaxed">{{ $event->description }}</p>
                    </div>

                    <div>
                        <div class="text-muted-foreground space-y-2 text-xs">
                            {{-- Date --}}
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-calendar" />
                                <span>
                                    {{ \Carbon\Carbon::parse($event->event_at)->format('d/m/Y') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-clock" />
                                <span>
                                    {{ \Carbon\Carbon::parse($event->start_at)->format('H:i:s') }} -
                                    {{ \Carbon\Carbon::parse($event->end_at)->format('H:i:s') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-map-pin" />
                                <span>{{ $event->location }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-users" />
                                <span>{{ $event->attendees_count }} / {{ $event->max_attendees }} participants</span>
                            </div>
                        </div>
                        <div class="actions">
                            @if ($event->isAttending() && ! $event->isPast())
                                <x-filament::button
                                    wire:click="attend({{$event->getKey()}})"
                                    class="focus-visible:ring-ring bg-primary text-shadow-black-500 hover:bg-primary/90 mt-2 inline-flex h-9 w-full items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                                >
                                    Join
                                </x-filament::button>
                            @elseif ($event->onWaitlist() && ! $event->isPast())
                                <x-filament::button
                                    wire:click="attend({{$event->getKey()}})"
                                    class="focus-visible:ring-ring bg-primary hover:bg-primary/90 mt-2 inline-flex h-9 w-full items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition-colors text-shadow-black focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                                >
                                    Join Waitlist
                                </x-filament::button>
                            @elseif ($event->participate(auth()->user()->getKey()) === true && ! $event->isPast())
                                <x-filament::button
                                    wire:click="leave({{$event->getKey()}})"
                                    class="focus-visible:ring-ring bg-primary hover:bg-primary/90 mt-2 inline-flex h-9 w-full items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition-colors text-shadow-black focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                                >
                                    Leave
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>
    <x-filament::pagination :paginator="$events" />
</x-filament-panels::page>
