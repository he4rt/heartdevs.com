<?php

declare(strict_types=1);

namespace He4rt\Season\Contracts;

use He4rt\Season\Collections\SeasonCollection;
use He4rt\Season\Entities\SeasonEntity;

interface SeasonRepository
{
    public function getAll(): SeasonCollection;

    public function getCurrent(): SeasonEntity;
}
