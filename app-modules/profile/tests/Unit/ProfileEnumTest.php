<?php

declare(strict_types=1);

use He4rt\Profile\Enums\SeniorityLevel;

test('seniority level has pt br labels', function (): void {
    expect(SeniorityLevel::Junior->label())->toBe('Júnior')
        ->and(SeniorityLevel::Pleno->label())->toBe('Pleno')
        ->and(SeniorityLevel::Senior->label())->toBe('Sênior')
        ->and(SeniorityLevel::Specialist->label())->toBe('Especialista')
        ->and(SeniorityLevel::Lead->label())->toBe('Lead');
});
