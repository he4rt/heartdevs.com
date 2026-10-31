<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Stream;

use Closure;
use He4rt\IntegrationSlido\Broadcasting\SlidoQuestionChanged;
use He4rt\IntegrationSlido\Broadcasting\SlidoQuestionPatched;
use He4rt\IntegrationSlido\Broadcasting\SlidoQuestionsSynced;
use He4rt\IntegrationSlido\Data\SlidoEventLocation;
use He4rt\IntegrationSlido\Data\SlidoParticipantToken;
use He4rt\IntegrationSlido\Data\SlidoQuestion;
use He4rt\IntegrationSlido\Data\SlidoQuestionPatch;
use He4rt\IntegrationSlido\Transport\SlidoClient;
use He4rt\Streaming\Streamer\Models\Streamer;
use Throwable;

final class SlidoStreamSession
{
    private ?SlidoParticipantToken $token = null;

    /**
     * @param  Closure(string): void  $log
     */
    public function __construct(
        private readonly SlidoClient $client,
        private readonly QuestionFeed $feed,
        private readonly SlidoEventLocation $location,
        private readonly Streamer $streamer,
        private readonly Closure $log,
    ) {}

    public function streamUrl(): string
    {
        $query = http_build_query([
            'clientId' => mb_substr(md5($this->streamer->getKey()), 0, 15),
            'slidoappVersion' => 'SlidoPresentApp/88.7.0 (web)',
            'EIO' => 4,
            'transport' => 'websocket',
        ]);

        return sprintf('%s/%s/stream/v0.5/stream-sio/?%s', mb_rtrim(config()->string('integration-slido.stream_url'), '/'), $this->location->cluster, $query);
    }

    public function syncQuestions(): void
    {
        $questions = $this->client->questions($this->location, $this->token());

        event(new SlidoQuestionsSynced($this->streamer, $questions));

        ($this->log)(sprintf('Sincronizou %d pergunta(s) visíveis.', count($questions)));
    }

    /**
     * @return list<string>
     */
    public function handle(string $raw): array
    {
        $frame = SocketIoFrame::parse($raw);

        return match ($frame->kind) {
            FrameKind::Open => [SocketIoFrame::CONNECT],
            FrameKind::Connected => [$this->subscribeFrame()],
            FrameKind::Ping => [SocketIoFrame::PONG],
            FrameKind::Ack => $this->acknowledge($frame),
            FrameKind::Event => $this->broadcast($frame),
            FrameKind::ConnectError, FrameKind::Disconnected, FrameKind::Unknown => $this->ignore($frame, $raw),
        };
    }

    /**
     * @return list<string>
     */
    public function refreshToken(): array
    {
        try {
            $this->token = $this->client->refreshParticipantToken($this->token());
            ($this->log)('Renovou o token de participante.');
        } catch (Throwable $throwable) {
            ($this->log)('Refresh recusado, emitindo token novo: '.$throwable->getMessage());
            $this->token = $this->client->issueParticipantToken($this->location);
        }

        return [$this->subscribeFrame()];
    }

    public function secondsUntilRefresh(): int
    {
        return $this->token()->secondsUntilRefresh();
    }

    private function token(): SlidoParticipantToken
    {
        return $this->token ??= $this->client->issueParticipantToken($this->location);
    }

    private function subscribeFrame(): string
    {
        return SocketIoFrame::subscribe($this->token()->accessToken, $this->location->streamTarget());
    }

    /**
     * @return list<string>
     */
    private function acknowledge(SocketIoFrame $frame): array
    {
        ($this->log)($frame->isSubscriptionAccepted() ? 'Assinatura aceita pelo Slido.' : 'Slido recusou a assinatura.');

        return [];
    }

    /**
     * @return list<string>
     */
    private function broadcast(SocketIoFrame $frame): array
    {
        $question = $this->feed->questionFrom($frame);

        if ($question instanceof SlidoQuestion) {
            event(new SlidoQuestionChanged($this->streamer, $question));

            return [];
        }

        $patch = $this->feed->patchFrom($frame);

        if ($patch instanceof SlidoQuestionPatch) {
            event(new SlidoQuestionPatched($this->streamer, $patch));

            return [];
        }

        if (!$this->feed->isKnown($frame)) {
            ($this->log)(sprintf('Evento novo do Slido: %s %s', $frame->eventName, json_encode($frame->params(), JSON_UNESCAPED_UNICODE)));
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function ignore(SocketIoFrame $frame, string $raw): array
    {
        ($this->log)(sprintf('Frame %s: %s', $frame->kind->name, mb_substr($raw, 0, 200)));

        return [];
    }
}
