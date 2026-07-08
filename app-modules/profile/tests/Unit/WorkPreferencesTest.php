<?php

declare(strict_types=1);

use He4rt\Profile\Data\WorkPreferences;
use He4rt\Profile\Enums\EmploymentType;

test('defaults to false booleans and no employment types', function (): void {
    $preferences = new WorkPreferences();

    expect($preferences->hasDisability)->toBeFalse()
        ->and($preferences->willingToRelocate)->toBeFalse()
        ->and($preferences->isOpenToRemote)->toBeFalse()
        ->and($preferences->employmentTypes)->toBeEmpty();
});

test('makeFromPayload parses booleans and employment types', function (): void {
    $preferences = WorkPreferences::makeFromPayload([
        'has_disability' => true,
        'willing_to_relocate' => false,
        'is_open_to_remote' => true,
        'employment_types' => ['pj', 'freelance'],
    ]);

    expect($preferences->hasDisability)->toBeTrue()
        ->and($preferences->willingToRelocate)->toBeFalse()
        ->and($preferences->isOpenToRemote)->toBeTrue()
        ->and($preferences->employmentTypes)->toBe([EmploymentType::IndependentContractor, EmploymentType::Freelancer]);
});

test('makeFromPayload drops invalid employment types and dedupes', function (): void {
    $preferences = WorkPreferences::makeFromPayload([
        'employment_types' => ['pj', 'hacker', 'pj', 'clt'],
    ]);

    expect($preferences->employmentTypes)->toBe([EmploymentType::IndependentContractor, EmploymentType::SalariedEmployee]);
});

test('makeFromPayload handles an empty payload', function (): void {
    $preferences = WorkPreferences::makeFromPayload([]);

    expect($preferences->hasDisability)->toBeFalse()
        ->and($preferences->employmentTypes)->toBeEmpty();
});

test('toArray serializes employment types to their backed values', function (): void {
    $preferences = new WorkPreferences(employmentTypes: [EmploymentType::IndependentContractor, EmploymentType::SalariedEmployee]);

    expect($preferences->toArray())->toBe([
        'has_disability' => false,
        'willing_to_relocate' => false,
        'is_open_to_remote' => false,
        'employment_types' => ['pj', 'clt'],
    ]);
});

test('toArray round-trips through makeFromPayload', function (): void {
    $original = new WorkPreferences(
        hasDisability: true,
        willingToRelocate: true,
        isOpenToRemote: false,
        employmentTypes: [EmploymentType::SalariedEmployee, EmploymentType::Freelancer],
    );

    expect(WorkPreferences::makeFromPayload($original->toArray()))->toEqual($original);
});
