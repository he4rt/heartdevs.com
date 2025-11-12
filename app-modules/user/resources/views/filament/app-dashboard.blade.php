@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Date;
    $userExperience = $this->stats->experience;
    $level = $this->stats->level;

    $reputation = $this->stats->reputation;
    $experienceRequiredForNextLevel = $this->stats->experienceProgress;
    $experienceRemaining = $this->stats->experiencePercentageRemaining;
    $nextLevelExperience = $experienceRequiredForNextLevel + $this->stats->experience;
    $experiencePercentage = $nextLevelExperience > 0 ? ($userExperience / $nextLevelExperience) * 100 : 0;

    $user = auth()->user();

    $address = $user?->address;

    $userFullAddress = $address ? implode(', ', array_filter([$address->city ?? null, $address->state ?? null, $address->country ?? null])) : '';

    $userName = $user?->name ?? '';
    $profileAbout = $user?->information?->about ?? '';
    $profileAvatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($user?->name ?? '') . '&background=0D8ABC&color=fff';
    $githubUrl = $user?->information?->github_url ?? null;
    $linkedinUrl = $user?->information?->linkedin_url ?? null;
@endphp

<x-filament-panels::page>
    <div class="w-full max-w-sm lg:flex lg:max-w-full lg:gap-4">
        <!-- Profile area -->
        <x-filament::card class="lg:w-1/2">
            <h2 class="mb-2 text-base font-semibold">Profile</h2>

            <div data-slot="card-content" class="">
                <div class="flex items-start gap-3">
                    <span
                        data-slot="avatar"
                        class="relative flex size-8 h-12 w-12 shrink-0 overflow-hidden rounded-full"
                    >
                        <img data-slot="avatar-image" class="aspect-square size-full" src="{{ $profileAvatarUrl }}" />
                    </span>
                    <div class="flex-1"><h3 class="text-base font-bold">{{ $userName }}</h3></div>
                </div>
                <p class="text-foreground my-2 text-xs">
                    {{ $profileAbout }}
                </p>

                @if (! empty($userFullAddress))
                    <div class="text-muted-foreground my-2 flex items-center gap-1.5 text-xs">
                        <x-heroicon-c-map-pin class="text-chart-1 h-4 w-4" />
                        <span>{{ $userFullAddress }}</span>
                    </div>
                @endif

                @if (! empty($githubUrl) || ! empty($linkedinUrl))
                    <div class="flex gap-2">
                        @if (! empty($githubUrl))
                            <a
                                href="{{ $githubUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                data-slot="button"
                                class="focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 has-[&gt;svg]:px-2.5 inline-flex h-7 shrink-0 items-center justify-center gap-1.5 rounded-md border bg-transparent px-3 text-xs font-medium whitespace-nowrap shadow-xs transition-all outline-none focus-visible:ring-[3px] disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4"
                            >
                                <x-filament::icon icon="fab-github" />
                                GitHub
                            </a>
                        @endif

                        @if (! empty($linkedinUrl))
                            <a
                                href="{{ $linkedinUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                data-slot="button"
                                class="focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 has-[&gt;svg]:px-2.5 inline-flex h-7 shrink-0 items-center justify-center gap-1.5 rounded-md border bg-transparent px-3 text-xs font-medium whitespace-nowrap shadow-xs transition-all outline-none focus-visible:ring-[3px] disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4"
                            >
                                <x-filament::icon icon="fab-linkedin" />
                                LinkedIn
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </x-filament::card>

        <!--Character Stats -->
        <x-filament::card class="lg:w-1/2">
            <h2 class="mb-2 text-base font-semibold">Character Stats</h2>

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
                            <span>{{ $nextLevelExperience }}</span>
                            XP
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div
                        class="fi-progress fi-color-primary relative h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"
                    >
                        <div
                            class="fi-progress-bar bg-primary-500 absolute top-0 left-0 h-full transition-all duration-500"
                            style="width: {{ $experiencePercentage }}%"
                        ></div>
                    </div>

                    <div class="mt-0.5 text-right text-sm text-gray-500 dark:text-gray-400">
                        <span>{{ (int) $experienceRequiredForNextLevel }}</span>
                        to next level
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-muted flex items-center gap-2 rounded-lg p-2">
                        <x-heroicon-o-trophy class="text-chart-2 h-6 w-6" />
                        <div>
                            <p class="text-sm font-bold">Reputation</p>
                            <p class="text-muted-foreground">{{ $reputation }}</p>
                        </div>
                    </div>

                    <div class="bg-muted flex items-center gap-2 rounded-lg p-2">
                        <x-heroicon-o-gift class="text-chart-3 h-6 w-6" />
                        <div>
                            <p class="text-xs font-semibold">Daily Bonus</p>
                            <p class="text-muted-foreground text-sm">
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
