<?php

declare(strict_types=1);

namespace He4rt\Community\UpcomingEvent\Models;

use Carbon\CarbonInterface;
use He4rt\Community\Database\Factories\UpcomingEventFactory;
use He4rt\Community\UpcomingEvent\Enums\UpcomingEventCategory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property UpcomingEventCategory $category
 * @property int|null $week_day
 * @property string|null $time
 * @property CarbonInterface|null $event_at
 * @property string|null $location
 * @property string|null $external_url
 * @property bool $is_active
 * @property bool $skip_next_occurrence
 * @property int $sort_order
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Table(name: 'upcoming_events')]
final class UpcomingEvent extends Model implements HasMedia
{
    /** @use HasFactory<UpcomingEventFactory> */
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->useDisk('public');
    }

    public function nextOccurrence(): ?CarbonInterface
    {
        if ($this->event_at instanceof CarbonInterface) {
            return $this->event_at;
        }

        if ($this->week_day === null || $this->time === null) {
            return null;
        }

        $occurrence = $this->resolveRecurringOccurrence();

        if ($this->skip_next_occurrence) {
            return $occurrence->addWeek();
        }

        return $occurrence;
    }

    protected static function newFactory(): UpcomingEventFactory
    {
        return UpcomingEventFactory::new();
    }

    protected function casts(): array
    {
        return [
            'category' => UpcomingEventCategory::class,
            'week_day' => 'integer',
            'is_active' => 'boolean',
            'skip_next_occurrence' => 'boolean',
            'event_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    private function resolveRecurringOccurrence(): CarbonInterface
    {
        $weekDay = (int) $this->week_day;
        $time = (string) $this->time;

        $now = now();

        $base = $now->dayOfWeek === $weekDay && $now->format('H:i') < $time
            ? $now
            : $now->copy()->next($weekDay);

        return $base->setTimeFromTimeString($time);
    }
}
