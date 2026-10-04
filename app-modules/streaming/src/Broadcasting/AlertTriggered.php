<?php

declare(strict_types=1);

namespace He4rt\Streaming\Broadcasting;

use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\StreamEvent\Data\StreamActor;
use He4rt\Streaming\StreamEvent\Data\StreamEventDetails;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AlertTriggered implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Streamer $streamer,
        public StreamEventType $type,
        public ?StreamActor $actor = null,
        public ?StreamEventDetails $details = null,
        public bool $isTest = false,
    ) {}

    public static function fromEvent(StreamEvent $event): self
    {
        return new self(
            streamer: $event->streamer,
            type: $event->type,
            actor: $event->actor(),
            details: $event->details,
        );
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->streamer->overlayChannel())];
    }

    public function broadcastAs(): string
    {
        return 'alert.triggered';
    }

    /**
     * @return array{type: string, actor: array{login: string, displayName: string}|null, details: array<string, int|string|null>|null, isTest: bool}
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->type->value,
            'actor' => $this->actor?->toBroadcast(),
            'details' => $this->details?->toArray(),
            'isTest' => $this->isTest,
        ];
    }
}
