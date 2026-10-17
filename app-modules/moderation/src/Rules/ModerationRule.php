<?php

declare(strict_types=1);

namespace He4rt\Moderation\Rules;

use Carbon\CarbonInterface;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Table(name: 'moderation_rules')]
final class ModerationRule extends Model
{
    use HasUuids;

    /**
     * Returns the PCRE compile error for a regex rule pattern, or null when it compiles.
     */
    public static function regexError(string $pattern): ?string
    {
        return self::runRegex($pattern, '')['error'];
    }

    public function matches(string $text): bool
    {
        return match ($this->type) {
            'keyword' => $this->matchesKeyword($text),
            'regex' => $this->matchesRegex($text),
            default => false,
        };
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

    /**
     * Runs the pattern without letting PHP warnings escape, so a broken rule never breaks the pipeline.
     *
     * @return array{result: int|false, error: string|null}
     */
    private static function runRegex(string $pattern, string $subject): array
    {
        $error = null;

        set_error_handler(static function (int $errno, string $message) use (&$error): bool {
            $error = $message;

            return true;
        });

        try {
            $result = preg_match(self::delimit($pattern), $subject);
        } finally {
            restore_error_handler();
        }

        return ['result' => $result, 'error' => $error];
    }

    /**
     * Wraps the pattern in `~` delimiters, escaping any `~` it contains that is not already escaped.
     */
    private static function delimit(string $pattern): string
    {
        $escaped = preg_replace_callback(
            '/\\\\.|~/s',
            static fn (array $match): string => $match[0] === '~' ? '\\~' : $match[0],
            $pattern,
        );

        return '~'.$escaped.'~i';
    }

    private function matchesKeyword(string $text): bool
    {
        $keywords = array_map(trim(...), explode(',', $this->pattern));
        $lowerText = mb_strtolower($text);

        return array_any($keywords, fn (string $keyword): bool => str_contains($lowerText, mb_strtolower($keyword)));
    }

    private function matchesRegex(string $text): bool
    {
        ['result' => $result, 'error' => $error] = self::runRegex($this->pattern, $text);

        if ($result === false) {
            Log::warning('Moderation rule has an invalid regex pattern.', [
                'rule_id' => $this->id,
                'error' => $error ?? preg_last_error_msg(),
            ]);

            return false;
        }

        return $result === 1;
    }
}
