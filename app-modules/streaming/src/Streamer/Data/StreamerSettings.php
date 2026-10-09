<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Data;

use He4rt\Streaming\Enums\OverlayScene;

final readonly class StreamerSettings
{
    public function __construct(
        public CoworkingSettings $coworking = new CoworkingSettings,
        public StartingSoonSettings $startingSoon = new StartingSoonSettings,
        public VoiceSettings $voice = new VoiceSettings,
        public ChatSettings $chat = new ChatSettings,
        public AlertSettings $alerts = new AlertSettings,
        public MutedChatters $mutedChatters = new MutedChatters,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $scenes = is_array($payload['scenes'] ?? null) ? $payload['scenes'] : [];
        $alerts = is_array($payload['alerts'] ?? null) ? $payload['alerts'] : [];
        $mutedChatters = is_array($payload['muted_chatters'] ?? null) ? $payload['muted_chatters'] : [];

        return new self(
            coworking: CoworkingSettings::fromArray(self::scenePayload($scenes, OverlayScene::Coworking)),
            startingSoon: StartingSoonSettings::fromArray(self::scenePayload($scenes, OverlayScene::StartingSoon)),
            voice: VoiceSettings::fromArray(self::scenePayload($scenes, OverlayScene::Voice)),
            chat: ChatSettings::fromArray(self::scenePayload($scenes, OverlayScene::Chat)),
            alerts: AlertSettings::fromArray($alerts),
            mutedChatters: MutedChatters::fromArray($mutedChatters),
        );
    }

    public function forScene(OverlayScene $scene): SceneSettings
    {
        return match ($scene) {
            OverlayScene::Coworking => $this->coworking,
            OverlayScene::StartingSoon => $this->startingSoon,
            OverlayScene::Voice => $this->voice,
            OverlayScene::Chat => $this->chat,
        };
    }

    public function withScene(SceneSettings $settings): self
    {
        return new self(
            coworking: $settings instanceof CoworkingSettings ? $settings : $this->coworking,
            startingSoon: $settings instanceof StartingSoonSettings ? $settings : $this->startingSoon,
            voice: $settings instanceof VoiceSettings ? $settings : $this->voice,
            chat: $settings instanceof ChatSettings ? $settings : $this->chat,
            alerts: $this->alerts,
            mutedChatters: $this->mutedChatters,
        );
    }

    public function withAlerts(AlertSettings $alerts): self
    {
        return new self($this->coworking, $this->startingSoon, $this->voice, $this->chat, $alerts, $this->mutedChatters);
    }

    public function withMutedChatters(MutedChatters $mutedChatters): self
    {
        return new self($this->coworking, $this->startingSoon, $this->voice, $this->chat, $this->alerts, $mutedChatters);
    }

    /**
     * @return array{scenes: array<string, array<string, string|int|null>>, alerts: array<string, bool>, muted_chatters: list<array{platform: string, chatter_id: string, display_name: string, muted_at: string}>}
     */
    public function toArray(): array
    {
        $scenes = [];

        foreach (OverlayScene::cases() as $scene) {
            $scenes[$scene->value] = $this->forScene($scene)->toArray();
        }

        return [
            'scenes' => $scenes,
            'alerts' => $this->alerts->toArray(),
            'muted_chatters' => $this->mutedChatters->toArray(),
        ];
    }

    /**
     * @param  array<array-key, mixed>  $scenes
     * @return array<array-key, mixed>
     */
    private static function scenePayload(array $scenes, OverlayScene $scene): array
    {
        return is_array($scenes[$scene->value] ?? null) ? $scenes[$scene->value] : [];
    }
}
