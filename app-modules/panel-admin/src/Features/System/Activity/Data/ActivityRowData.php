<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Activity\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelData\Data;

final class ActivityRowData extends Data
{
    public function __construct(
        public int $id,
        public string $description,
        public ?string $event,
        public ?string $subjectType,
        public ?string $subjectLabel,
        public ?string $causerName,
        public ?int $riskScore,
        public ?string $riskLevel,
        public string $createdAt,
    ) {}

    public static function fromModel(Activity $activity): self
    {
        $score = $activity->getAttribute('risk_score');
        $level = $activity->getAttribute('risk_level');

        return new self(
            id: (int) $activity->getKey(),
            description: $activity->description,
            event: $activity->event,
            subjectType: self::typeLabel($activity->subject_type),
            subjectLabel: self::subjectLabel($activity),
            causerName: self::name($activity->causer),
            riskScore: is_numeric($score) ? (int) $score : null,
            riskLevel: is_string($level) ? $level : null,
            createdAt: $activity->created_at?->toIso8601String() ?? '',
        );
    }

    public static function typeLabel(?string $morphType): ?string
    {
        if ($morphType === null) {
            return null;
        }

        return class_basename(Relation::getMorphedModel($morphType) ?? $morphType);
    }

    private static function subjectLabel(Activity $activity): ?string
    {
        return self::name($activity->subject) ?? ($activity->subject_id === null ? null : '#'.$activity->subject_id);
    }

    private static function name(?Model $model): ?string
    {
        $name = $model?->getAttribute('name');

        return is_string($name) ? $name : null;
    }
}
