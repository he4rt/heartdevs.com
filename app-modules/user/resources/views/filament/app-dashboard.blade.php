@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Date;
    $userExperience = $this->stats->experience;
    $nextLevelXp = $this->stats->percentageExperience + $this->stats->experience;
    $level = $this->stats->level;

    $reputation = $this->stats->reputation;
    $xpProgress = $this->stats->experienceProgress;
    $xpRemaining = $this->stats->experiencePercentageRemaining;

    $address = auth()->user()?->address;
    $about = auth()->user()?->information?->about;
@endphp

<x-filament-panels::page>
    <div class="w-full max-w-sm lg:flex lg:max-w-full lg:gap-4">
        <!-- Profile area -->
        <x-filament::card class="lg:w-1/2">
            <div
                data-slot="card-header"
                class="grid auto-rows-min grid-rows-[auto_auto] items-start gap-2 px-6 pb-3 has-data-[slot=card-action]:grid-cols-[1fr_auto] [.border-b]:pb-6"
            >
                <div data-slot="card-title" class="text-base font-semibold">Profile</div>
            </div>
            <div data-slot="card-content" class="space-y-3 px-6">
                <div class="flex items-start gap-3">
                    <span
                        data-slot="avatar"
                        class="relative flex size-8 h-12 w-12 shrink-0 overflow-hidden rounded-full"
                    >
                        <img
                            data-slot="avatar-image"
                            class="aspect-square size-full"
                            src="/placeholder.svg?height=64&amp;width=64"
                        />
                    </span>
                    <div class="flex-1"><h3 class="text-base font-bold">{{ auth()->user()->name }}</h3></div>
                </div>
                <p class="text-foreground text-xs">
                    Full-stack developer passionate about open source and community building.
                </p>
                <div class="text-muted-foreground flex items-center gap-1.5 text-xs">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-map-pin h-3 w-3"
                    >
                        <path
                            d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"
                        ></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <span>São Paulo, SP, Brazil</span>
                </div>
                <div class="flex gap-2">
                    <a
                        href="https://github.com/paula"
                        target="_blank"
                        rel="noopener noreferrer"
                        data-slot="button"
                        class="focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 has-[&gt;svg]:px-2.5 inline-flex h-7 shrink-0 items-center justify-center gap-1.5 rounded-md border bg-transparent px-3 text-xs font-medium whitespace-nowrap shadow-xs transition-all outline-none focus-visible:ring-[3px] disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4"
                    >
                        <x-filament::icon icon="fab-github" />
                        GitHub
                    </a>
                    <a
                        href="https://linkedin.com/in/paula"
                        target="_blank"
                        rel="noopener noreferrer"
                        data-slot="button"
                        class="focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 has-[&gt;svg]:px-2.5 inline-flex h-7 shrink-0 items-center justify-center gap-1.5 rounded-md border bg-transparent px-3 text-xs font-medium whitespace-nowrap shadow-xs transition-all outline-none focus-visible:ring-[3px] disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4"
                    >
                        <x-filament::icon icon="fab-linkedin" />
                        LinkedIn
                    </a>
                </div>
            </div>
        </x-filament::card>

        <!--Character Stats -->
        <x-filament::card class="lg:w-1/2">
            <x-slot name="header" class="pb-3">
                <x-filament::section.heading class="text-base">Character Stats</x-filament::section.heading>
            </x-slot>

            <div class="space-y-4">
                <!-- Level and XP -->
                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-o-bolt class="text-chart-1 h-4 w-4" />
                            <span class="text-sm font-semibold">
                                Level
                                <span>{{ $level }}</span>
                            </span>
                        </div>
                        <span class="text-muted-foreground text-xs">
                            <span>{{ $userExperience }}</span>
                            /
                            <span>{{ $nextLevelXp }}</span>
                            XP
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div
                        class="fi-progress fi-color-primary relative h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"
                    >
                        <div
                            class="fi-progress-bar bg-primary-500 absolute top-0 left-0 h-full transition-all duration-500"
                            style="width: {{ $xpRemaining }}%"
                        ></div>
                    </div>

                    <div class="mt-0.5 text-right text-[10px] text-gray-500 dark:text-gray-400">
                        <span>{{ (int) $xpRemaining }}%</span>
                        to next level
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-muted flex items-center gap-2 rounded-lg p-2">
                        <x-heroicon-o-trophy class="text-chart-2 h-6 w-6" />
                        <div>
                            <p class="text-sm font-bold">Reputation</p>
                            <p class="text-muted-foreground text-[10px]">{{ $reputation }}</p>
                        </div>
                    </div>

                    <div class="bg-muted flex items-center gap-2 rounded-lg p-2">
                        <x-heroicon-o-gift class="text-chart-3 h-6 w-6" />
                        <div>
                            <p class="text-xs font-semibold">Daily Bonus</p>
                            <p class="text-muted-foreground text-[10px]">
                                {{ Date::today()->format('d/M/Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::card>
    </div>

    <!-- Events -->
    <div class="mt-4 w-full">
        <x-filament::card>
            <x-slot name="header" class="pb-3">
                <x-filament::section.heading class="text-white">Events</x-filament::section.heading>
            </x-slot>

            <div class="space-y-2">
                @forelse ($this->events as $event)
                    <div
                        class="meeting-card border-border hover:bg-muted/50 translate-y-2 transform rounded-lg border p-3 transition-colors"
                    >
                        <!-- Header -->
                        <div class="mb-2 flex items-start justify-between">
                            <h4 class="text-sm font-semibold">{{ $event->title }}</h4>
                            <x-filament::badge color="primary" size="sm">
                                {{ $event->end_at < now() ? 'Past' : 'Upcoming' }}
                            </x-filament::badge>
                        </div>

                        <!-- Details -->
                        <div class="text-muted-foreground space-y-1 text-xs">
                            <!-- Day -->
                            <div class="flex items-center gap-1.5">
                                <x-heroicon-o-calendar class="h-3 w-3" />
                                <span>{{ Carbon::parse($event->start_at)->format('l') }}</span>
                            </div>

                            <!-- Time -->
                            <div class="flex items-center gap-1.5">
                                <x-heroicon-o-clock class="h-3 w-3" />
                                <span>
                                    {{ Carbon::parse($event->starts_at)->format('h:i A') }}
                                    -
                                    {{ Carbon::parse($event->ends_at)->format('h:i A') }}
                                </span>
                            </div>

                            <!-- Participants -->
                            <div class="flex items-center gap-1.5">
                                <x-heroicon-o-users class="h-3 w-3" />
                                <span>{{ $event->participants_count }} participants</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted-foreground text-center text-xs">No events scheduled for now.</p>
                @endforelse
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
