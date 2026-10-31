<?php

declare(strict_types=1);

use He4rt\IntegrationSlido\Stream\SlidoAudience;
use He4rt\Streaming\Health\Contracts\OverlayConnections;
use He4rt\Streaming\Streamer\Data\PubSettings;
use He4rt\Streaming\Streamer\Data\StreamerSettings;
use He4rt\Streaming\Streamer\Models\Streamer;

/**
 * @param  array<string, int|null>  $openOverlays  overlay count per streamer id; missing ids count as one
 */
function fakeOpenOverlays(array $openOverlays): void
{
    app()->instance(OverlayConnections::class, new readonly class($openOverlays) implements OverlayConnections
    {
        public function __construct(private array $openOverlays) {}

        public function count(Streamer $streamer): ?int
        {
            return array_key_exists($streamer->id, $this->openOverlays) ? $this->openOverlays[$streamer->id] : 1;
        }
    });
}

function pubStreamer(?string $eventHash, bool $isActive = true): Streamer
{
    $factory = $isActive ? Streamer::factory() : Streamer::factory()->disabled();

    return $factory->create(['settings' => (new StreamerSettings)->withScene(new PubSettings($eventHash))]);
}

function audienceIds(): array
{
    return array_map(
        fn (array $streamers): array => array_map(fn (Streamer $streamer): string => $streamer->id, $streamers),
        resolve(SlidoAudience::class)->current(),
    );
}

test('junta no mesmo evento os streamers com a overlay He4rt Pub aberta', function (): void {
    $first = pubStreamer('jC9L7Lu93Vw3ssNhB92Pvb');
    $second = pubStreamer('jC9L7Lu93Vw3ssNhB92Pvb');
    $other = pubStreamer('Outro5Evento6Hash');
    fakeOpenOverlays([]);

    expect(audienceIds())->toEqualCanonicalizing([
        'jC9L7Lu93Vw3ssNhB92Pvb' => [$first->id, $second->id],
        'Outro5Evento6Hash' => [$other->id],
    ]);
});

test('deixa de fora quem fechou a overlay, está desativado ou não configurou o Slido', function (): void {
    $closed = pubStreamer('jC9L7Lu93Vw3ssNhB92Pvb');
    pubStreamer('jC9L7Lu93Vw3ssNhB92Pvb', isActive: false);
    pubStreamer(eventHash: null);
    fakeOpenOverlays([$closed->id => 0]);

    expect(audienceIds())->toBeEmpty();
});

test('mantém o streamer quando o Reverb não responde a contagem de overlays', function (): void {
    $streamer = pubStreamer('jC9L7Lu93Vw3ssNhB92Pvb');
    fakeOpenOverlays([$streamer->id => null]);

    expect(audienceIds())->toBe(['jC9L7Lu93Vw3ssNhB92Pvb' => [$streamer->id]]);
});
