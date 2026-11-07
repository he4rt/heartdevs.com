<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Data;

final readonly class SlidoQuestionPatch
{
    private const array FIELDS = [
        'score' => 'score',
        'is_highlighted' => 'isHighlighted',
        'is_answered' => 'isAnswered',
    ];

    /**
     * @param  array<string, int|bool>  $changes
     */
    public function __construct(
        public int $id,
        public array $changes,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function tryFromPayload(array $payload): ?self
    {
        $id = $payload['event_question_id'] ?? null;

        if (!is_int($id)) {
            return null;
        }

        $changes = [];

        foreach (self::FIELDS as $source => $target) {
            $value = $payload[$source] ?? null;

            if (is_int($value) || is_bool($value)) {
                $changes[$target] = $value;
            }
        }

        return $changes === [] ? null : new self($id, $changes);
    }

    /**
     * @return array{id: int, changes: array<string, int|bool>}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'changes' => $this->changes];
    }
}
