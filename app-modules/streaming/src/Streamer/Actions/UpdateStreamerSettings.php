<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Actions;

use He4rt\Streaming\Broadcasting\OverlaySettingsUpdated;
use He4rt\Streaming\Broadcasting\OverlaySourcesUpdated;
use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Streamer\Data\StreamerSettings;
use He4rt\Streaming\Streamer\Models\Streamer;

final readonly class UpdateStreamerSettings
{
    public function handle(Streamer $streamer, StreamerSettings $settings): Streamer
    {
        $previous = $streamer->settings;

        $streamer->update(['settings' => $settings]);

        foreach (OverlayScene::cases() as $scene) {
            $sceneSettings = $settings->forScene($scene)->toArray();
            $sceneChanged = $previous->forScene($scene)->toArray() !== $sceneSettings;

            if ($sceneChanged) {
                event(new OverlaySettingsUpdated($streamer, $scene, $sceneSettings));
            }

            $sceneSourcesChanged = $previous->sceneSources->chosenFor($scene) !== $settings->sceneSources->chosenFor($scene);

            if ($sceneSourcesChanged) {
                event(new OverlaySourcesUpdated($streamer, $scene));
            }
        }

        return $streamer;
    }
}
