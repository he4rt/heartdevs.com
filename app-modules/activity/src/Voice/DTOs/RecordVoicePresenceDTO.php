<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\DTOs;

use He4rt\Activity\Voice\Enums\VoicePresenceEnum;
use He4rt\Activity\Voice\ValueObjects\VoiceTransition;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class RecordVoicePresenceDTO
{
    public function __construct(
        public IdentityProvider $provider,
        public string $externalAccountId,
        public VoicePresenceEnum $presence,
        public string $channelName,
        public ?string $channelId = null,
        public ?string $username = null,
    ) {}

    /**
     * Fan a single voice-state change out into one DTO per transition.
     *
     * The identity (provider, account, username) is shared across every
     * transition of the change; the presence and channel come from each
     * already-resolved VoiceTransition — a move emits both a `left` and a
     * `joined`.
     *
     * @param  list<VoiceTransition>  $transitions
     * @return list<self>
     */
    public static function makeMany(
        IdentityProvider $provider,
        string $externalAccountId,
        array $transitions,
        ?string $username = null,
    ): array {
        return array_map(fn (VoiceTransition $transition): self => new self(
            provider: $provider,
            externalAccountId: $externalAccountId,
            presence: $transition->presence,
            channelName: $transition->channelName,
            channelId: $transition->channelId,
            username: $username,
        ), $transitions);
    }
}
