<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries\PeriodStats;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * With rangeDays = 7, PeriodStats produces 7 daily blocks covering the seven
 * full days that end at today's start-of-day (in the display timezone). Freezing
 * "now" to 2026-06-15 noon makes those blocks deterministically 2026-06-08
 * (block 0) through 2026-06-14 (block 6).
 */
beforeEach(function (): void {
    $this->displayTimezone = config('app.display_timezone');

    Date::setTestNow(Date::create(2_026, 6, 15, 12, 0, 0, $this->displayTimezone));
});

afterEach(function (): void {
    Date::setTestNow();
});

test('it buckets messages, distinct users and voice joins into the correct daily blocks', function (): void {
    $tz = $this->displayTimezone;

    $identityA = ExternalIdentity::factory()->create();
    $identityB = ExternalIdentity::factory()->create();

    $firstBlockNoon = Date::create(2_026, 6, 8, 12, 0, 0, $tz);   // block 0 (oldest day)
    $lastBlockNoon = Date::create(2_026, 6, 14, 12, 0, 0, $tz);   // block 6 (most recent full day)
    $todayNoon = Date::create(2_026, 6, 15, 12, 0, 0, $tz);       // outside every block

    // Block 0: three messages from two distinct identities.
    Message::factory()->count(2)->create([
        'external_identity_id' => $identityA->id,
        'sent_at' => $firstBlockNoon,
    ]);
    Message::factory()->create([
        'external_identity_id' => $identityB->id,
        'sent_at' => $firstBlockNoon,
    ]);

    // Block 6: two messages from a single identity.
    Message::factory()->count(2)->create([
        'external_identity_id' => $identityA->id,
        'sent_at' => $lastBlockNoon,
    ]);

    // Today's message falls outside all seven blocks and must not be counted.
    Message::factory()->create([
        'external_identity_id' => $identityA->id,
        'sent_at' => $todayNoon,
    ]);

    $insertVoice = static function (string $state, CarbonInterface $occurredAt) use ($identityA): void {
        DB::table('voice_messages')->insert([
            'external_identity_id' => $identityA->id,
            'channel_name' => 'general',
            'state' => $state,
            'obtained_experience' => 0,
            'occurred_at' => $occurredAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    };

    // Block 6: one "joined" event (counted) and one "left" event (ignored by the query).
    $insertVoice('joined', $lastBlockNoon);
    $insertVoice('left', $lastBlockNoon);

    $blocks = new PeriodStats(7)->get()['blocks'];

    expect($blocks)->toHaveCount(7);

    // Oldest block: 3 messages, 2 distinct users, no voice.
    expect($blocks[0]['msgs'])->toBe(3)
        ->and($blocks[0]['users'])->toBe(2)
        ->and($blocks[0]['voice'])->toBe(0.0);

    // Most recent full day: 2 messages, 1 user, voice = round(1 * 0.75, 1).
    expect($blocks[6]['msgs'])->toBe(2)
        ->and($blocks[6]['users'])->toBe(1)
        ->and($blocks[6]['voice'])->toBe(0.8);

    // The idle middle blocks are empty.
    foreach ([1, 2, 3, 4, 5] as $idleBlock) {
        expect($blocks[$idleBlock]['msgs'])->toBe(0)
            ->and($blocks[$idleBlock]['users'])->toBe(0);
    }

    // Today's message is excluded from every block (5 in-range messages total).
    expect(array_sum(array_column($blocks, 'msgs')))->toBe(5);
});
