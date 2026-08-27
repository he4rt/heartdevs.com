<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Enums;

/**
 * Implementation status of a Plan, derived from its task checkboxes.
 */
enum PlanStatus: string
{
    case Proposed = 'proposed';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    /**
     * Derive the status from the number of completed and total checkbox steps.
     */
    public static function fromProgress(int $completed, int $total): self
    {
        if ($total <= 0 || $completed <= 0) {
            return self::Proposed;
        }

        if ($completed >= $total) {
            return self::Completed;
        }

        return self::InProgress;
    }

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'Proposto',
            self::InProgress => 'Em progresso',
            self::Completed => 'Concluído',
        };
    }

    /**
     * Flux badge color.
     */
    public function color(): string
    {
        return match ($this) {
            self::Proposed => 'amber',
            self::InProgress => 'blue',
            self::Completed => 'green',
        };
    }
}
