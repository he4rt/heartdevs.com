<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Stream;

use Closure;
use He4rt\IntegrationSlido\Exceptions\SlidoSubscriptionRejected;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use Throwable;

final class SlidoConnection
{
    private const string ORIGIN = 'https://wall.sli.do';

    private const int MAX_RECONNECT_DELAY_SECONDS = 60;

    private ?WebSocket $socket = null;

    private ?TimerInterface $refreshTimer = null;

    private ?TimerInterface $reconnectTimer = null;

    private int $reconnectAttempts = 0;

    private bool $isClosed = false;

    /**
     * @param  Closure(string): void  $log
     */
    public function __construct(
        public readonly SlidoStreamSession $session,
        private readonly LoopInterface $loop,
        private readonly Closure $log,
    ) {}

    public function open(): void
    {
        if ($this->isClosed) {
            return;
        }

        (new Connector($this->loop))($this->session->streamUrl(), [], ['Origin' => self::ORIGIN])->then(
            function (WebSocket $socket): void {
                if ($this->isClosed) {
                    $socket->close();

                    return;
                }

                $this->socket = $socket;

                $socket->on('message', function (MessageInterface $message) use ($socket): void {
                    $this->receive($socket, (string) $message);
                });

                $socket->on('close', function (?int $code = null, ?string $reason = null): void {
                    if ($this->isClosed) {
                        return;
                    }

                    ($this->log)(sprintf('Conexão fechada (%s %s).', $code ?? '-', $reason ?? ''));
                    $this->reconnect();
                });

                try {
                    $this->scheduleTokenRefresh();
                } catch (Throwable $throwable) {
                    ($this->log)('Falhou ao preparar o token: '.$throwable->getMessage());
                    $socket->close();
                }
            },
            function (Throwable $throwable): void {
                ($this->log)('Falhou ao conectar no Slido: '.$throwable->getMessage());
                $this->reconnect();
            },
        );
    }

    public function close(): void
    {
        $this->isClosed = true;
        $this->cancelTimers();
        $this->socket?->close();
        $this->socket = null;
    }

    private function receive(WebSocket $socket, string $raw): void
    {
        try {
            $this->sendAll($socket, $this->session->handle($raw));

            if ($this->session->isSubscribed()) {
                $this->reconnectAttempts = 0;
            }
        } catch (SlidoSubscriptionRejected $rejected) {
            ($this->log)($rejected->getMessage());
            $socket->close();
        } catch (Throwable $throwable) {
            ($this->log)('Falhou ao tratar um frame do Slido: '.$throwable->getMessage());
        }
    }

    private function reconnect(): void
    {
        $this->socket = null;
        $this->cancelTimers();

        if ($this->isClosed) {
            return;
        }

        $delay = min(2 ** $this->reconnectAttempts, self::MAX_RECONNECT_DELAY_SECONDS);
        $this->reconnectAttempts++;

        ($this->log)(sprintf('Reconectando em %ds…', $delay));

        $this->reconnectTimer = $this->loop->addTimer($delay, fn () => $this->open());
    }

    private function scheduleTokenRefresh(): void
    {
        if ($this->refreshTimer instanceof TimerInterface) {
            $this->loop->cancelTimer($this->refreshTimer);
        }

        $this->refreshTimer = $this->loop->addTimer($this->session->secondsUntilRefresh(), function (): void {
            try {
                $frames = $this->session->refreshToken();
            } catch (Throwable $throwable) {
                ($this->log)('Falhou ao renovar o token: '.$throwable->getMessage());
                $this->socket?->close();

                return;
            }

            if ($this->socket instanceof WebSocket) {
                $this->sendAll($this->socket, $frames);
            }

            $this->scheduleTokenRefresh();
        });
    }

    private function cancelTimers(): void
    {
        foreach ([$this->refreshTimer, $this->reconnectTimer] as $timer) {
            if ($timer instanceof TimerInterface) {
                $this->loop->cancelTimer($timer);
            }
        }

        $this->refreshTimer = null;
        $this->reconnectTimer = null;
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
