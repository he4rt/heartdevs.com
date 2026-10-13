<?php

declare(strict_types=1);

namespace He4rt\Streaming\Chat\Data;

use He4rt\Streaming\Enums\ChatFragmentKind;

final readonly class ChatFragment
{
    public function __construct(
        public ChatFragmentKind $kind,
        public string $text,
        public ?string $emoteId = null,
        public ?string $emoteUrl = null,
    ) {}

    public static function text(string $text): self
    {
        return new self(ChatFragmentKind::Text, $text);
    }

    public static function emote(string $text, string $emoteId, string $emoteUrl): self
    {
        return new self(ChatFragmentKind::Emote, $text, $emoteId, $emoteUrl);
    }

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): ?self
    {
        $text = $payload['text'] ?? null;

        if (!is_string($text)) {
            return null;
        }

        $emoteId = $payload['emote_id'] ?? null;
        $emoteUrl = $payload['emote_url'] ?? null;
        $isEmote = ($payload['kind'] ?? null) === ChatFragmentKind::Emote->value && is_string($emoteId) && is_string($emoteUrl);

        return $isEmote ? self::emote($text, $emoteId, $emoteUrl) : self::text($text);
    }

    /**
     * @return array{kind: string, text: string, emote_id: string|null, emote_url: string|null}
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind->value,
            'text' => $this->text,
            'emote_id' => $this->emoteId,
            'emote_url' => $this->emoteUrl,
        ];
    }

    /**
     * @return array{kind: 'text', text: string}|array{kind: 'emote', id: string|null, url: string|null}
     */
    public function toBroadcast(): array
    {
        return match ($this->kind) {
            ChatFragmentKind::Text => ['kind' => 'text', 'text' => $this->text],
            ChatFragmentKind::Emote => ['kind' => 'emote', 'id' => $this->emoteId, 'url' => $this->emoteUrl],
        };
    }
}
