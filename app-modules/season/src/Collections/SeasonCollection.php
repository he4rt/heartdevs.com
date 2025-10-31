<?php

declare(strict_types=1);

namespace He4rt\Season\Collections;

use ArrayIterator;
use DateMalformedStringException;
use He4rt\Season\Entities\SeasonEntity;
use JsonSerializable;

final class SeasonCollection extends ArrayIterator implements JsonSerializable
{
    /**
     * @throws DateMalformedStringException
     */
    public static function make(array $seasonsPayload): self
    {
        $seasons = [];
        foreach ($seasonsPayload as $seasonPayload) {
            $seasons[] = SeasonEntity::make($seasonPayload);
        }

        return new self($seasons);
    }

    public function add(SeasonEntity $seasonEntity): self
    {
        $this->append($seasonEntity);

        return $this;
    }

    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }
}
