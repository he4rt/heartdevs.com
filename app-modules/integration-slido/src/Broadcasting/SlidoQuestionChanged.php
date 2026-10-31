<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Broadcasting;

use He4rt\IntegrationSlido\Data\SlidoQuestion;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;

final class SlidoQuestionChanged implements ShouldBroadcastNow, ShouldRescue
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public Streamer $streamer,
        public SlidoQuestion $question,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->streamer->overlayChannel())];
    }

    public function broadcastAs(): string
    {
        return 'pub.question';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->question->toArray();
    }
}
