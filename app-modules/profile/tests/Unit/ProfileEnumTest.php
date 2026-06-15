<?php

declare(strict_types=1);

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Enums\StartAvailability;

test('seniority level implements filament enum interfaces', function (): void {
    expect(SeniorityLevel::Junior)
        ->toBeInstanceOf(HasLabel::class)
        ->toBeInstanceOf(HasColor::class)
        ->toBeInstanceOf(HasIcon::class);

    expect(SeniorityLevel::Junior->getLabel())->toBeString()->not->toBeEmpty()
        ->and(SeniorityLevel::Pleno->getLabel())->toBeString()->not->toBeEmpty()
        ->and(SeniorityLevel::Senior->getLabel())->toBeString()->not->toBeEmpty();
});

test('social platform implements filament enum interfaces', function (): void {
    expect(SocialPlatform::Instagram)
        ->toBeInstanceOf(HasLabel::class)
        ->toBeInstanceOf(HasIcon::class);

    expect(SocialPlatform::Instagram->getLabel())->toBeString()->not->toBeEmpty()
        ->and(SocialPlatform::Twitter->getLabel())->toBeString()->not->toBeEmpty()
        ->and(SocialPlatform::Website->getLabel())->toBeString()->not->toBeEmpty()
        ->and(SocialPlatform::YouTube->getLabel())->toBeString()->not->toBeEmpty()
        ->and(SocialPlatform::Bluesky->getLabel())->toBeString()->not->toBeEmpty();
});

test('start availability implements filament enum interfaces', function (): void {
    expect(StartAvailability::Immediate)
        ->toBeInstanceOf(HasLabel::class)
        ->toBeInstanceOf(HasColor::class)
        ->toBeInstanceOf(HasIcon::class);

    expect(StartAvailability::Immediate->getLabel())->toBeString()->not->toBeEmpty()
        ->and(StartAvailability::OneWeek->getLabel())->toBeString()->not->toBeEmpty()
        ->and(StartAvailability::Negotiable->getLabel())->toBeString()->not->toBeEmpty();
});

test('all seniority level cases have distinct colors and icons', function (): void {
    $colors = array_map(fn (SeniorityLevel $level) => $level->getColor(), SeniorityLevel::cases());
    $icons = array_map(fn (SeniorityLevel $level) => $level->getIcon(), SeniorityLevel::cases());

    expect($colors)->toHaveCount(3)
        ->and($icons)->toHaveCount(3);
});
