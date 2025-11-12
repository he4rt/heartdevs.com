<x-filament-panels::page>
    @php
        $events = $this->getTableRecords();
    @endphp

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($events as $event)
            <div
                wire:key="{{ $event->getKey() }}"
                class="bg-card text-card-foreground h-full rounded-lg border shadow-sm transition-shadow hover:shadow-lg"
            >
                <div class="flex flex-col p-6 pb-3">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-base leading-tight font-semibold tracking-tight">
                            {{ $event->title }}
                        </h3>
                        <x-filament::badge
                            class="focus:ring-ring inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors focus:ring-2 focus:ring-offset-2 focus:outline-none"
                        >
                            {{ $event->end_at < now() ? 'Past' : 'Upcoming' }}
                        </x-filament::badge>

                        <x-filament::icon wire:click="view({{$event->getKey()}})" icon="heroicon-m-ellipsis-vertical" />
                    </div>

                    <x-filament::badge
                        class="focus:ring-ring border-input bg-background hover:bg-accent hover:text-accent-foreground mt-2 inline-flex w-fit items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:ring-2 focus:ring-offset-2 focus:outline-none"
                        icon="heroicon-m-tag"
                    >
                        {{ $event->event_type->getLabel() }}
                    </x-filament::badge>
                </div>

                <div class="space-y-3 p-6 pt-0">
                    <p class="text-muted-foreground text-xs leading-relaxed">{{ $event->description }}</p>

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
                    @if ($event->attendees()->first()->pivot->status === \He4rt\Events\Enums\AttendingStatusEnum::Attending && ! $event->isPast())
                        <x-filament::button
                            wire:click="attend({{$event->getKey()}})"
                            class="focus-visible:ring-ring bg-primary text-shadow-black-500 hover:bg-primary/90 mt-2 inline-flex h-9 w-full items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                        >
                            Join
                        </x-filament::button>
                    @elseif ($event->attendees()->first()->pivot->status === \He4rt\Events\Enums\AttendingStatusEnum::Waitlist && ! $event->isPast())
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
        @endforeach
    </div>
    <x-filament::pagination :paginator="$events" />
</x-filament-panels::page>
