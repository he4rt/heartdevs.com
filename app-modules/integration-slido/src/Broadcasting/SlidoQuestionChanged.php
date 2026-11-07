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

    /**
     * @param  list<Streamer>  $streamers
     */
    public function __construct(
        public array $streamers,
        public SlidoQuestion $question,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return array_map(fn (Streamer $streamer): PrivateChannel => new PrivateChannel($streamer->overlayChannel()), $this->streamers);
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
