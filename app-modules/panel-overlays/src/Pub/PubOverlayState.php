<?php

declare(strict_types=1);

namespace He4rt\PanelOverlays\Pub;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use He4rt\IntegrationSlido\Data\SlidoEventLocation;
use He4rt\IntegrationSlido\Data\SlidoParticipantToken;
use He4rt\IntegrationSlido\Data\SlidoQuestion;
use He4rt\IntegrationSlido\Transport\SlidoClient;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class PubOverlayState
{
    private const int QR_SIZE = 360;

    public function __construct(private SlidoClient $client) {}

    /**
     * @return array{event: array{hash: string, name: string, code: string, isActive: bool, participantUrl: string}, questions: list<array<string, mixed>>, qrSvg: string}|null
     */
    public function for(Streamer $streamer): ?array
    {
        $eventHash = $streamer->settings->pub->slidoEventHash;

        if ($eventHash === null) {
            return null;
        }

        try {
            $location = $this->client->locate($eventHash);
            $event = $this->client->event($location);
            $questions = $this->client->questions($location, $this->participantToken($eventHash, $location));
        } catch (Throwable $throwable) {
            Log::warning('Slido indisponível ao montar a overlay da He4rt Pub.', ['hash' => $eventHash, 'error' => $throwable->getMessage()]);

            return null;
        }

        return [
            'event' => $event->toArray(),
            'questions' => array_map(fn (SlidoQuestion $question): array => $question->toArray(), $questions),
            'qrSvg' => $this->qrSvg($event->participantUrl),
        ];
    }

    private function participantToken(string $eventHash, SlidoEventLocation $location): SlidoParticipantToken
    {
        $cacheKey = 'integration-slido:overlay-token:'.$eventHash;
        $cached = Cache::get($cacheKey);

        if ($cached instanceof SlidoParticipantToken && !$cached->needsRefresh()) {
            return $cached;
        }

        $token = $this->client->issueParticipantToken($location);

        Cache::put($cacheKey, $token, $token->expiresAt);

        return $token;
    }

    private function qrSvg(string $participantUrl): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(self::QR_SIZE, margin: 1), new SvgImageBackEnd()));

        return $writer->writeString($participantUrl);
    }
}
