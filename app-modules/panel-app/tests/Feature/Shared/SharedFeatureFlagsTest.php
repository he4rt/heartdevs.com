<?php

declare(strict_types=1);

use App\Support\FeatureFlags\Actions\EnableFeatureFlag;
use App\Support\FeatureFlags\Actions\OpenFeatureGate;
use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\FeatureRegistry;
use App\Support\FeatureFlags\FeatureScope;
use App\Support\FeatureFlags\StoredFeatureFlag;
use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;
use Tests\Fixtures\FeatureFlags\BetaDashboard;
use Tests\Fixtures\FeatureFlags\MaintenanceBanner;
use Tests\Fixtures\FeatureFlags\NewCheckout;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    resolve(FeatureRegistry::class)->register([NewCheckout::class, BetaDashboard::class, MaintenanceBanner::class]);
});

it('shares the user and global flags that the pages read', function (): void {
    $ana = User::factory()->create();
    resolve(OpenFeatureGate::class)->handle(new BetaDashboard);
    resolve(EnableFeatureFlag::class)->handle(new BetaDashboard, $ana);
    actingAs($ana);

    get(route('panel-app::dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('features', ['beta-dashboard' => true, 'maintenance-banner' => false]));
});

it('reads a user flag as off for a user who does not have it', function (): void {
    resolve(OpenFeatureGate::class)->handle(new BetaDashboard);
    resolve(OpenFeatureGate::class)->handle(new MaintenanceBanner);
    actingAs(User::factory()->create());

    get(route('panel-app::dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('features.beta-dashboard', expected: false)
            ->where('features.maintenance-banner', expected: true)
            ->missing('features.new-checkout'));

    expect(StoredFeatureFlag::query()->count())->toBe(2);
});

it('keeps a user flag that is not shared on the server', function (): void {
    $internalTool = new class implements FeatureFlag
    {
        public function key(): string
        {
            return 'internal-tool';
        }

        public function label(): string
        {
            return 'Internal tool';
        }

        public function description(): string
        {
            return 'A flag that no page reads.';
        }

        public function scope(): FeatureScope
        {
            return FeatureScope::User;
        }
    };
    $ana = User::factory()->create();
    resolve(FeatureRegistry::class)->register([$internalTool::class]);
    resolve(OpenFeatureGate::class)->handle($internalTool);
    resolve(EnableFeatureFlag::class)->handle($internalTool, $ana);
    actingAs($ana);

    get(route('panel-app::dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->missing('features.internal-tool'));
});

it('shares no flags with a guest', function (): void {
    get(route('panel-app::welcome'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('features', []));
});
