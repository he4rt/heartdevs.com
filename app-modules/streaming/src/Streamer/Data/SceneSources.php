<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Data;

use He4rt\Streaming\Enums\OverlayScene;

final readonly class SceneSources
{
    /**
     * @param  array<string, list<string>>  $sourceIdsByScene  scene value => chosen source ids
     */
    public function __construct(
        public array $sourceIdsByScene = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $sourceIdsByScene = [];

        foreach (OverlayScene::cases() as $scene) {
            $sourceIds = $payload[$scene->value] ?? null;

            if (!is_array($sourceIds)) {
                continue;
            }

            $validIds = array_values(array_filter($sourceIds, fn (mixed $id): bool => is_string($id) && $id !== ''));

            if ($validIds !== []) {
                $sourceIdsByScene[$scene->value] = $validIds;
            }
        }

        return new self($sourceIdsByScene);
    }

    /**
     * @return list<string>
     */
    public function chosenFor(OverlayScene $scene): array
    {
        return $this->sourceIdsByScene[$scene->value] ?? [];
    }

    /**
     * Narrows the streamer's sources to the ones the scene uses.
     *
     * Returns null when the scene uses every source: nothing was chosen, or every chosen source
     * is gone.
     *
     * @param  list<string>  $existingSourceIds
     * @return list<string>|null
     */
    public function allowedFor(OverlayScene $scene, array $existingSourceIds): ?array
    {
        $stillExisting = array_values(array_intersect($this->chosenFor($scene), $existingSourceIds));

        return $stillExisting === [] ? null : $stillExisting;
    }

    /**
     * @param  list<string>  $sourceIds  an empty list makes the scene use every source
     */
    public function with(OverlayScene $scene, array $sourceIds): self
    {
        $sourceIdsByScene = $this->sourceIdsByScene;
        unset($sourceIdsByScene[$scene->value]);

        $chosenIds = array_values(array_unique($sourceIds));

        if ($chosenIds !== []) {
            $sourceIdsByScene[$scene->value] = $chosenIds;
        }

        return new self($sourceIdsByScene);
    }

    /**
     * @return array<string, list<string>>
     */
    public function toArray(): array
    {
        return $this->sourceIdsByScene;
    }
}
