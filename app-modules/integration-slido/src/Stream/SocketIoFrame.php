<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Stream;

use JsonException;

final readonly class SocketIoFrame
{
    public const string CONNECT = '40';

    public const string PONG = '3';

    private const int SUBSCRIBE_ACK_ID = 0;

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public function __construct(
        public FrameKind $kind,
        public ?string $eventName = null,
        public array $payload = [],
    ) {}

    public static function parse(string $raw): self
    {
        return match (true) {
            $raw === '2' => new self(FrameKind::Ping),
            str_starts_with($raw, '40') => new self(FrameKind::Connected, payload: self::decode(mb_substr($raw, 2))),
            str_starts_with($raw, '41') => new self(FrameKind::Disconnected),
            str_starts_with($raw, '42') => self::event(mb_substr($raw, 2)),
            str_starts_with($raw, '43') => new self(FrameKind::Ack, payload: self::decode(self::withoutAckId(mb_substr($raw, 2)))),
            str_starts_with($raw, '44') => new self(FrameKind::ConnectError, payload: self::decode(mb_substr($raw, 2))),
            str_starts_with($raw, '0') => new self(FrameKind::Open, payload: self::decode(mb_substr($raw, 1))),
            default => new self(FrameKind::Unknown),
        };
    }

    public static function subscribe(string $accessToken, string $target): string
    {
        $packet = ['subscribe', ['accessToken' => $accessToken, 'targets' => [$target]]];

        return sprintf('42%d%s', self::SUBSCRIBE_ACK_ID, json_encode($packet, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<array-key, mixed>
     */
    public function params(): array
    {
        $params = $this->payload['params'] ?? null;

        return is_array($params) ? $params : [];
    }

    public function isSubscriptionAccepted(): bool
    {
        $first = $this->payload[0] ?? null;

        return $this->kind === FrameKind::Ack && is_array($first) && ($first['code'] ?? null) === 200;
    }

    private static function event(string $json): self
    {
        $packet = self::decode($json);
        $name = $packet[0] ?? null;
        $data = $packet[1] ?? null;

        if (!is_string($name)) {
            return new self(FrameKind::Unknown);
        }

        return new self(FrameKind::Event, $name, is_array($data) ? $data : []);
    }

    private static function withoutAckId(string $raw): string
    {
        return (string) preg_replace('/^\d+/', '', $raw);
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function decode(string $json): array
    {
        if ($json === '') {
            return [];
        }

        try {
            $decoded = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
