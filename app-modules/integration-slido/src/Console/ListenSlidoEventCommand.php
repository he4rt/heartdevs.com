<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Console;

use He4rt\IntegrationSlido\Stream\QuestionFeed;
use He4rt\IntegrationSlido\Stream\SlidoStreamSession;
use He4rt\IntegrationSlido\Transport\SlidoClient;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;
use Throwable;

#[Description(description: 'Escuta as perguntas de um evento do Slido e envia para a overlay do streamer')]
#[Signature(signature: 'slido:listen {streamer : username do streamer dono da overlay} {event? : hash do evento no Slido (padrão: o configurado na cena He4rt Pub)}')]
final class ListenSlidoEventCommand extends Command
{
    private const string ORIGIN = 'https://wall.sli.do';

    private const int MAX_RECONNECT_DELAY_SECONDS = 60;

    private ?WebSocket $socket = null;

    private ?TimerInterface $refreshTimer = null;

    private int $reconnectAttempts = 0;

    public function handle(SlidoClient $client, QuestionFeed $feed): int
    {
        $streamer = Streamer::query()
            ->whereHas('user', fn (Builder $query) => $query->where('username', $this->argument('streamer')))
            ->first();

        if (!$streamer instanceof Streamer) {
            $this->error(sprintf("Streamer '%s' não encontrado.", $this->argument('streamer')));

            return self::FAILURE;
        }

        $eventHash = $this->argument('event') ?? $streamer->settings->pub->slidoEventHash;

        if (!is_string($eventHash) || $eventHash === '') {
            $this->error('Nenhum evento do Slido. Passe o hash ou configure a cena He4rt Pub no painel.');

            return self::FAILURE;
        }

        $location = $client->locate($eventHash);
        $event = $client->event($location);

        $this->info(sprintf('Escutando "%s" (%s) para a overlay de %s.', $event->name, $location->uuid, $this->argument('streamer')));

        $session = new SlidoStreamSession($client, $feed, $location, $streamer, fn (string $line) => $this->line(sprintf('[%s] %s', now()->format('H:i:s'), $line)));

        $this->connect($session);

        Loop::run();

        return self::SUCCESS;
    }

    private function connect(SlidoStreamSession $session): void
    {
        try {
            $session->syncQuestions();
        } catch (Throwable $throwable) {
            $this->warn('Falhou ao sincronizar as perguntas: '.$throwable->getMessage());
        }

        (new Connector(Loop::get()))($session->streamUrl(), [], ['Origin' => self::ORIGIN])->then(
            function (WebSocket $socket) use ($session): void {
                $this->socket = $socket;
                $this->reconnectAttempts = 0;
                $this->scheduleTokenRefresh($session);

                $socket->on('message', function (MessageInterface $message) use ($socket, $session): void {
                    $this->sendAll($socket, $session->handle((string) $message));
                });

                $socket->on('close', function (?int $code = null, ?string $reason = null) use ($session): void {
                    $this->warn(sprintf('Conexão fechada (%s %s).', $code ?? '-', $reason ?? ''));
                    $this->reconnect($session);
                });
            },
            function (Throwable $throwable) use ($session): void {
                $this->warn('Falhou ao conectar no Slido: '.$throwable->getMessage());
                $this->reconnect($session);
            },
        );
    }

    private function reconnect(SlidoStreamSession $session): void
    {
        $this->socket = null;

        if ($this->refreshTimer instanceof TimerInterface) {
            Loop::cancelTimer($this->refreshTimer);
        }

        $delay = min(2 ** $this->reconnectAttempts, self::MAX_RECONNECT_DELAY_SECONDS);
        $this->reconnectAttempts++;

        $this->line(sprintf('Reconectando em %ds…', $delay));

        Loop::addTimer($delay, fn () => $this->connect($session));
    }

    private function scheduleTokenRefresh(SlidoStreamSession $session): void
    {
        if ($this->refreshTimer instanceof TimerInterface) {
            Loop::cancelTimer($this->refreshTimer);
        }

        $this->refreshTimer = Loop::addTimer($session->secondsUntilRefresh(), function () use ($session): void {
            try {
                $frames = $session->refreshToken();
            } catch (Throwable $throwable) {
                $this->warn('Falhou ao renovar o token: '.$throwable->getMessage());
                $this->socket?->close();

                return;
            }

            if ($this->socket instanceof WebSocket) {
                $this->sendAll($this->socket, $frames);
            }

            $this->scheduleTokenRefresh($session);
        });
    }

    /**
     * @param  list<string>  $frames
     */
    private function sendAll(WebSocket $socket, array $frames): void
    {
        foreach ($frames as $frame) {
            $socket->send($frame);
        }
    }
}
