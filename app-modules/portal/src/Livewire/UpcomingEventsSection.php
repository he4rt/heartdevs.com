<?php

declare(strict_types=1);

namespace He4rt\Portal\Livewire;

use Carbon\CarbonInterface;
use He4rt\Community\UpcomingEvent\Models\UpcomingEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class UpcomingEventsSection extends Component
{
    /**
     * @return Collection<int, array{event: UpcomingEvent, occurrence: CarbonInterface}>
     */
    #[Computed]
    public function upcomingEvents(): Collection
    {
        if (!app()->isProduction()) {
            return $this->fetchUpcomingEvents();
        }

        return Cache::remember('portal:upcoming-events', now()->addHour(), fn () => $this->fetchUpcomingEvents());
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function schemaOrg(): array
    {
        $events = $this->upcomingEvents()
            ->map(function (array $item): array {
                $event = $item['event'];
                $occurrence = $item['occurrence'];

                return [
                    '@type' => 'Event',
                    'name' => $event->title,
                    'description' => $event->description,
                    'startDate' => $occurrence->toIso8601String(),
                    'eventStatus' => 'https://schema.org/EventScheduled',
                    'eventAttendanceMode' => $event->location
                        ? 'https://schema.org/OfflineEventAttendanceMode'
                        : 'https://schema.org/OnlineEventAttendanceMode',
                    'location' => $event->location
                        ? ['@type' => 'Place', 'name' => $event->location]
                        : ['@type' => 'VirtualLocation', 'url' => $event->external_url ?? 'https://discord.gg/he4rt'],
                    'url' => $event->external_url,
                    ...($event->getFirstMediaUrl('cover') ? ['image' => $event->getFirstMediaUrl('cover')] : []),
                ];
            })
            ->values();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $events
                ->map(fn (array $event, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'item' => $event,
                ])
                ->all(),
        ];
    }

    public function render(): View
    {
        return view('portal::sections.upcoming-events');
    }

    /**
     * @return Collection<int, array{event: UpcomingEvent, occurrence: CarbonInterface}>
     */
    private function fetchUpcomingEvents(): Collection
    {
        $events = [];

        foreach (UpcomingEvent::query()->where('is_active', operator: true)->orderBy('sort_order')->get() as $event) {
            $occurrence = $event->nextOccurrence();
            if ($occurrence === null) {
                continue;
            }

            if ($occurrence->isPast()) {
                continue;
            }

            $events[] = ['event' => $event, 'occurrence' => $occurrence];
        }

        usort($events, static fn (array $a, array $b): int => $a['occurrence']->getTimestamp() <=> $b['occurrence']->getTimestamp());

        return collect($events);
    }
}
