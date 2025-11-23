@props([
    'userImg',
    'username',
    'githubUsername',
    'ticketNumber',
    'githubLink' => '#',
    'githubText' => null,
    'twitchLink' => '#',
    'twitchText' => null,
    'eventDate' => '00/00, 15 horas',
    'eventSubtitle' => 'Ao vivo e gratuito',
])

@php
    $ticketNumberStr = (string) $ticketNumber;
    $numDigits = strlen($ticketNumberStr);
    $prefix = substr('000000', $numDigits);
@endphp

<div class="overflow-hidden">
    <x-he4rt::card :interactive="false" class="ticket-wrapper">
        <div class="ticket-content">
            <div class="ticket-profile">
                <div class="ticket-profile-content">
                    <div>
                        <x-he4rt::avatar size="2xl" src="{{ $userImg }}" alt="Image from {{ $username }}" />
                    </div>

                    <div class="ticket-profile-text">
                        <x-he4rt::heading size="sm">{{ $githubUsername }}</x-he4rt::heading>

                        <div class="ticket-profile-username">
                            <x-he4rt::button
                                :href="$githubLink"
                                variant="outline"
                                icon="fab-github"
                                class="text-text-medium w-fit border-none p-0 hover:bg-transparent"
                                icon-position="leading"
                            >
                                {{ $githubText }}
                            </x-he4rt::button>
                        </div>
                    </div>
                </div>

                <div class="ticket-event">
                    <div class="justify-self-center sm:justify-self-start">
                        <x-he4rt::logo size="sm" path="images/3pontos/logo.svg" class="mb-0!" />
                    </div>

                    <x-he4rt::text class="text-text-high font-semibold sm:justify-self-end">
                        {{ $eventDate }}
                    </x-he4rt::text>

                    <x-he4rt::text class="font-semibold">
                        {{ $eventSubtitle }}
                    </x-he4rt::text>

                    <div class="justify-self-center sm:justify-self-end">
                        <x-he4rt::button
                            :href="$twitchLink"
                            variant="outline"
                            icon="fab-twitch"
                            class="w-fit border-none p-0 hover:bg-transparent"
                            icon-position="leading"
                        >
                            {{ $twitchText }}
                        </x-he4rt::button>
                    </div>
                </div>
            </div>

            <div class="ticket-number-col">
                <x-he4rt::heading size="lg" class="ticket-number">N° {{ $prefix . $ticketNumber }}</x-he4rt::heading>
            </div>
        </div>
    </x-he4rt::card>
</div>
