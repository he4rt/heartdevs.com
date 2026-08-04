<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Exceptions;

use He4rt\Onboarding\Enums\OnboardingType;
use RuntimeException;

final class PrerequisiteNotMetException extends RuntimeException
{
    public function __construct(
        public readonly OnboardingType $type,
        public readonly OnboardingType $prerequisite,
    ) {
        parent::__construct(sprintf(
            'Onboarding [%s] bloqueado: pré-requisito [%s] não concluído.',
            $type->value,
            $prerequisite->value,
        ));
    }
}
