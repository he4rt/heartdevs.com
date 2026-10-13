<?php

declare(strict_types=1);

namespace He4rt\Streaming\StreamEvent\Actions;

use He4rt\Streaming\Broadcasting\AlertTriggered;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Data\GiftSubDetails;
use He4rt\Streaming\StreamEvent\Data\RaidDetails;
use He4rt\Streaming\StreamEvent\Data\StreamActor;
use He4rt\Streaming\StreamEvent\Data\StreamEventDetails;
use He4rt\Streaming\StreamEvent\Data\SubDetails;

final readonly class TriggerTestAlert
{
    public function handle(Streamer $streamer, StreamEventType $type): void
    {
        event(new AlertTriggered(
            streamer: $streamer,
            type: $type,
            actor: new StreamActor(platformId: '0', login: 'he4rtdevs', displayName: 'He4rtDevs'),
            details: $this->sampleDetails($type),
            isTest: true,
        ));
    }

    private function sampleDetails(StreamEventType $type): ?StreamEventDetails
    {
        return match ($type) {
            StreamEventType::Follow => null,
            StreamEventType::Sub => new SubDetails(months: 3),
            StreamEventType::GiftSub => new GiftSubDetails(total: 5),
            StreamEventType::Cheer => new CheerDetails(bits: 500),
            StreamEventType::Raid => new RaidDetails(viewers: 42),
        };
    }
}
