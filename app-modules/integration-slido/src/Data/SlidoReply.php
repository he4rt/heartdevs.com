<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Data;

final readonly class SlidoReply
{
    public function __construct(
        public int $id,
        public string $text,
        public ?string $authorName,
        public bool $isFromModerator,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function tryFromPayload(array $payload): ?self
    {
        $id = $payload['event_question_id'] ?? null;
        $text = $payload['text'] ?? null;

        if (!is_int($id) || !is_string($text)) {
            return null;
        }

        $attrs = is_array($payload['attrs'] ?? null) ? $payload['attrs'] : [];

        return new self(
            id: $id,
            text: $text,
            authorName: SlidoQuestion::authorNameOf($payload),
            isFromModerator: ($attrs['is_admin'] ?? false) === true,
        );
    }

    /**
     * @return array{id: int, text: string, authorName: string|null, isFromModerator: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'authorName' => $this->authorName,
            'isFromModerator' => $this->isFromModerator,
        ];
    }
}
