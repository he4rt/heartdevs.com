<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Exceptions;

use RuntimeException;

final class GateBlockedException extends RuntimeException
{
    public function __construct(
        public readonly string $step,
        public readonly string $reason,
    ) {
        parent::__construct(sprintf('Gate bloqueado para step [%s]: %s', $step, $reason));
    }
}
