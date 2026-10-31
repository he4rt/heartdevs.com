<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Data;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

final readonly class SlidoQuestion
{
    /**
     * @param  list<SlidoReply>  $replies
     */
    public function __construct(
        public int $id,
        public string $text,
        public ?string $authorName,
        public int $score,
        public bool $isHighlighted,
        public bool $isAnswered,
        public bool $isVisible,
        public CarbonImmutable $createdAt,
        public array $replies = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function tryFromPayload(array $payload): ?self
    {
        $id = $payload['event_question_id'] ?? null;
        $text = $payload['text'] ?? null;
        $isQuestion = ($payload['type'] ?? 'Question') === 'Question';

        if (!$isQuestion || !is_int($id) || !is_string($text)) {
            return null;
        }

        $isPublic = ($payload['is_public'] ?? true) === true;
        $isDeleted = ($payload['date_deleted'] ?? null) !== null;
        $createdAt = self::createdAtOf($payload['date_created'] ?? null);
        $pinnedReplies = is_array($payload['pinned_replies'] ?? null) ? $payload['pinned_replies'] : [];

        return new self(
            id: $id,
            text: $text,
            authorName: self::authorNameOf($payload),
            score: is_int($payload['score'] ?? null) ? $payload['score'] : 0,
            isHighlighted: ($payload['is_highlighted'] ?? false) === true,
            isAnswered: ($payload['is_answered'] ?? false) === true,
            isVisible: $isPublic && !$isDeleted,
            createdAt: $createdAt,
            replies: self::repliesOf($pinnedReplies),
        );
    }

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function authorNameOf(array $payload): ?string
    {
        if (($payload['is_anonymous'] ?? false) === true) {
            return null;
        }

        $author = is_array($payload['author'] ?? null) ? $payload['author'] : [];
        $name = $author['name'] ?? null;

        return is_string($name) && $name !== '' ? $name : null;
    }

    /**
     * @return array{id: int, text: string, authorName: string|null, score: int, isHighlighted: bool, isAnswered: bool, isVisible: bool, createdAt: string, replies: list<array{id: int, text: string, authorName: string|null, isFromModerator: bool}>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'authorName' => $this->authorName,
            'score' => $this->score,
            'isHighlighted' => $this->isHighlighted,
            'isAnswered' => $this->isAnswered,
            'isVisible' => $this->isVisible,
            'createdAt' => $this->createdAt->toIso8601String(),
            'replies' => array_map(fn (SlidoReply $reply): array => $reply->toArray(), $this->replies),
        ];
    }

    private static function createdAtOf(mixed $value): CarbonImmutable
    {
        if (!is_string($value)) {
            return CarbonImmutable::now();
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (InvalidFormatException) {
            return CarbonImmutable::now();
        }
    }

    /**
     * @param  array<array-key, mixed>  $pinnedReplies
     * @return list<SlidoReply>
     */
    private static function repliesOf(array $pinnedReplies): array
    {
        $replies = [];

        foreach ($pinnedReplies as $pinnedReply) {
            $reply = is_array($pinnedReply) ? SlidoReply::tryFromPayload($pinnedReply) : null;

            if ($reply instanceof SlidoReply) {
                $replies[] = $reply;
            }
        }

        return $replies;
    }
}
