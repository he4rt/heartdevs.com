<?php

declare(strict_types=1);

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use He4rt\Profile\Enums\SkillCategory;
use He4rt\Profile\Enums\SkillProficiency;

test('skill category implements filament enum interfaces with labels', function (): void {
    expect(SkillCategory::Language)
        ->toBeInstanceOf(HasLabel::class)
        ->toBeInstanceOf(HasColor::class)
        ->toBeInstanceOf(HasIcon::class);

    foreach (SkillCategory::cases() as $category) {
        expect($category->getLabel())->toBeString()->not->toBeEmpty();
    }
});

test('skill proficiency implements filament enum interfaces with labels', function (): void {
    expect(SkillProficiency::Beginner)
        ->toBeInstanceOf(HasLabel::class)
        ->toBeInstanceOf(HasColor::class)
        ->toBeInstanceOf(HasIcon::class);

    foreach (SkillProficiency::cases() as $proficiency) {
        expect($proficiency->getLabel())->toBeString()->not->toBeEmpty();
    }
});
