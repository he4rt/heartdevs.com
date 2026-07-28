<?php

declare(strict_types=1);

use He4rt\BotDiscord\Enums\EmpresarialRejectionReason;

it('exposes a distinct human-readable message for every rejection reason', function (): void {
    $messages = array_map(
        fn (EmpresarialRejectionReason $reason): string => $reason->message(),
        EmpresarialRejectionReason::cases(),
    );

    expect($messages)
        ->each->toStartWith('❌')
        ->and(array_unique($messages))->toHaveSameSize(EmpresarialRejectionReason::cases());
});

it('maps each case to its backed slug', function (): void {
    expect(EmpresarialRejectionReason::NotInTrackedRoom->value)->toBe('not-in-tracked-room')
        ->and(EmpresarialRejectionReason::MissingPartnerRole->value)->toBe('missing-partner-role')
        ->and(EmpresarialRejectionReason::UnknownCompany->value)->toBe('unknown-company');
});
