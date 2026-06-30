<?php

declare(strict_types=1);

namespace He4rt\Moderation\Rules;

use Carbon\CarbonInterface;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $name
 * @property string $type
 * @property Platform|null $platform
 * @property string $pattern
 * @property ViolationType $violation_type
 * @property Severity $severity
 * @property ActionType $action_on_match
 * @property bool $is_active
 * @property string|null $tenant_id
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Table('moderation_rules')]
final class ModerationRule extends Model
{
    use HasUuids;

    public function matches(string $text): bool
    {
        return match ($this->type) {
            'keyword' => $this->matchesKeyword($text),
            'regex' => $this->matchesRegex($text),
            default => false,
        };
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'violation_type' => ViolationType::class,
            'severity' => Severity::class,
            'action_on_match' => ActionType::class,
            'is_active' => 'boolean',
        ];
    }

    private function matchesKeyword(string $text): bool
    {
        $keywords = array_map(trim(...), explode(',', $this->pattern));
        $lowerText = mb_strtolower($text);

        return array_any($keywords, fn (string $keyword): bool => str_contains($lowerText, mb_strtolower($keyword)));
    }

    private function matchesRegex(string $text): bool
    {
        return (bool) @preg_match('~'.$this->pattern.'~i', $text);
    }
}
