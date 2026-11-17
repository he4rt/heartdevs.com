<?php

declare(strict_types=1);

use App\Support\FeatureFlags\Exceptions\DuplicateFeatureKey;
use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\FeatureRegistry;
use App\Support\FeatureFlags\FeatureScope;
use Tests\Fixtures\FeatureFlags\BetaDashboard;
use Tests\Fixtures\FeatureFlags\NewCheckout;

it('finds a registered flag by its key', function (): void {
    $registry = new FeatureRegistry;

    $registry->register([NewCheckout::class, BetaDashboard::class]);

    expect($registry->find('new-checkout'))->toBeInstanceOf(NewCheckout::class)
        ->and($registry->find('missing'))->toBeNull()
        ->and($registry->all())->toHaveCount(2);
});

it('keeps one entry when the same class registers twice', function (): void {
    $registry = new FeatureRegistry;

    $registry->register([NewCheckout::class]);
    $registry->register([NewCheckout::class]);

    expect($registry->all())->toHaveCount(1);
});

it('refuses two classes with the same key', function (): void {
    $impostor = new class implements FeatureFlag
    {
        public function key(): string
        {
            return 'new-checkout';
        }

        public function label(): string
        {
            return 'Impostor';
        }

        public function description(): string
        {
            return 'Another flag with a taken key.';
        }

        public function scope(): FeatureScope
        {
            return FeatureScope::Global;
        }
    };
    $registry = new FeatureRegistry;
    $registry->register([NewCheckout::class]);

    expect(fn () => $registry->register([$impostor::class]))->toThrow(DuplicateFeatureKey::class);
});
