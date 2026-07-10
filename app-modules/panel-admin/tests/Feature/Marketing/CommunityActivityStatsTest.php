<?php

declare(strict_types=1);

use App\Models\Address;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Marketing\Pages\Location\Queries\CommunityActivityStats;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();

    $this->tz = config('app.display_timezone');
    Date::setTestNow(Date::create(2_026, 6, 15, 12, 0, 0, $this->tz));

    Http::fake([
        'world.bmbc.cloud/api/countries*' => Http::response([
            'data' => [['id' => 31, 'iso2' => 'BR', 'iso3' => 'BRA', 'name' => 'Brazil']],
        ]),
        'world.bmbc.cloud/api/states*' => Http::response([
            'data' => [['id' => 1, 'name' => 'São Paulo']],
        ]),
    ]);
});

afterEach(function (): void {
    Date::setTestNow();
});

test('it counts distinct web and discord actives within the current window', function (): void {
    $tz = $this->tz;
    $tenant = Tenant::factory()->create();

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $userC = User::factory()->create();

    // Web: userA (twice) and userB post inside the 30-day window; userC only outside.
    Timeline::factory()->recycle($tenant)->create(['user_id' => $userA->id, 'created_at' => Date::create(2_026, 6, 10, 12, 0, 0, $tz)]);
    Timeline::factory()->recycle($tenant)->create(['user_id' => $userA->id, 'created_at' => Date::create(2_026, 6, 12, 12, 0, 0, $tz)]);
    Timeline::factory()->recycle($tenant)->create(['user_id' => $userB->id, 'created_at' => Date::create(2_026, 6, 1, 12, 0, 0, $tz)]);
    Timeline::factory()->recycle($tenant)->create(['user_id' => $userC->id, 'created_at' => Date::create(2_026, 1, 1, 12, 0, 0, $tz)]);

    // Discord: one distinct identity with a message inside the window.
    $identity = ExternalIdentity::factory()->recycle($tenant)->create();
    Message::factory()->recycle($tenant)->create(['external_identity_id' => $identity->id, 'sent_at' => Date::create(2_026, 6, 11, 12, 0, 0, $tz)]);

    // Location coverage: one located member.
    Address::factory()->forUser($userA)->create(['state' => 'São Paulo']);

    $stats = new CommunityActivityStats(30)->get();

    expect($stats->webActive)->toBe(2)
        ->and($stats->discordActive)->toBe(1)
        ->and($stats->locatedMembers)->toBe(1)
        ->and($stats->statesReached)->toBe(1)
        ->and($stats->statesTotal)->toBe(1);
});
