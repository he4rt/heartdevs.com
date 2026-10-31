<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Console;

use He4rt\IntegrationSlido\Data\SlidoEventLocation;
use He4rt\IntegrationSlido\Stream\QuestionFeed;
use He4rt\IntegrationSlido\Stream\SlidoAudience;
use He4rt\IntegrationSlido\Stream\SlidoConnection;
use He4rt\IntegrationSlido\Stream\SlidoStreamSession;
use He4rt\IntegrationSlido\Stream\SlidoSubscriptionPlan;
use He4rt\IntegrationSlido\Transport\SlidoClient;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use Throwable;

#[Description(description: 'Mantém uma conexão com cada evento do Slido que tem uma overlay He4rt Pub aberta')]
#[Signature(signature: 'slido:serve {--interval=15 : segundos entre cada conferência das overlays abertas}')]
final class ServeSlidoEventsCommand extends Command
{
    /** @var array<string, SlidoConnection> */
    private array $connections = [];

    /** @var array<string, SlidoEventLocation> */
    private array $locations = [];

    public function handle(SlidoClient $client, QuestionFeed $feed, SlidoAudience $audience): int
    {
        $loop = Loop::get();
        $interval = max(5, (int) $this->option('interval'));

        $this->log(sprintf('Conferindo as overlays He4rt Pub a cada %ds.', $interval));

        $reconcile = function () use ($client, $feed, $audience, $loop): void {
            try {
                $this->reconcile($client, $feed, $audience->current(), $loop);
            } catch (Throwable $throwable) {
                $this->log('Falhou ao conferir as overlays: '.$throwable->getMessage());
            }
        };

        $reconcile();
        $loop->addPeriodicTimer($interval, $reconcile);
        $loop->run();

        return self::SUCCESS;
    }

    /**
     * @param  array<string, list<Streamer>>  $audience
     */
    private function reconcile(SlidoClient $client, QuestionFeed $feed, array $audience, LoopInterface $loop): void
    {
        $plan = SlidoSubscriptionPlan::between(array_keys($audience), array_keys($this->connections));

        foreach ($plan->closing as $eventHash) {
            $this->connections[$eventHash]->close();
            unset($this->connections[$eventHash]);
            $this->log(sprintf('[%s] Nenhuma overlay aberta: desconectou.', $eventHash));
        }

        foreach ($plan->keeping as $eventHash) {
            $this->connections[$eventHash]->session->retarget($audience[$eventHash]);
        }

        foreach ($plan->opening as $eventHash) {
            $this->open($client, $feed, $eventHash, $audience[$eventHash], $loop);
        }
    }

    /**
     * @param  list<Streamer>  $streamers
     */
    private function open(SlidoClient $client, QuestionFeed $feed, string $eventHash, array $streamers, LoopInterface $loop): void
    {
        try {
            $location = $this->locations[$eventHash] ??= $client->locate($eventHash);
        } catch (Throwable $throwable) {
            $this->log(sprintf('[%s] Não achou o evento no Slido: %s', $eventHash, $throwable->getMessage()));

            return;
        }

        $log = fn (string $line) => $this->log(sprintf('[%s] %s', $eventHash, $line));
        $session = new SlidoStreamSession($client, $feed, $location, $streamers, $log);
        $connection = new SlidoConnection($session, $loop, $log);

        $this->connections[$eventHash] = $connection;
        $log(sprintf('Conectando para %d overlay(s).', count($streamers)));
        $connection->open();
    }

    private function log(string $line): void
    {
        $this->line(sprintf('[%s] %s', now()->format('H:i:s'), $line));
    }
}
