<?php

declare(strict_types=1);

namespace He4rt\Moderation\Contracts;

use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;

interface ContentClassifierContract
{
    public function classify(ModerationContentDTO $content): ClassificationResultDTO;

    public function name(): string;
}
