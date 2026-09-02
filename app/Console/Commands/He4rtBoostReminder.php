<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

#[Description(description: 'Informative reminder to run php artisan boost:install after setup')]
#[Signature(signature: 'he4rt:boost-reminder
        {--who= : Show the fun facts for a specific contributor (GitHub handle)}')]
final class He4rtBoostReminder extends Command
{
    private const string COLOR = '#782bf1';

    public function handle(): void
    {
        $this->displayLogo();

        warning('O Laravel Boost ainda não foi configurado neste ambiente.');

        note('Rode `php artisan boost:install` para habilitar o MCP e as guidelines de IA deste projeto.');

        $this->line('  Agentes de IA (Claude, Cursor, Copilot, etc.) não devem executar tarefas neste');
        $this->line('  repositório enquanto esse comando não for executado — veja CLAUDE.md / AGENTS.md.');
        $this->newLine();

        $this->displayFunFact();
    }

    private function displayFunFact(): void
    {
        /** @var list<array{name: string, github: string, facts: list<string>}> $contributors */
        $contributors = config('fun-facts', []);

        if ($contributors === []) {
            return;
        }

        $who = $this->option('who');
        $who = is_string($who) ? $who : null;

        $contributor = $who === null
            ? Arr::random($contributors)
            : Arr::first($contributors, fn (array $c): bool => strcasecmp($c['github'], $who) === 0);

        if (!$contributor) {
            $this->line("  Nenhum contribuidor encontrado no config/fun-facts.php com o GitHub '{$who}'.");

            return;
        }

        if ($contributor['facts'] === []) {
            $this->line("  {$contributor['name']} (@{$contributor['github']}) ainda não tem fun facts cadastrados.");

            return;
        }

        $fact = Arr::random($contributor['facts']);

        $this->line(sprintf('  <fg=%s;options=bold>Você sabia? — %s (@%s)</>', self::COLOR, $contributor['name'], $contributor['github']));
        $this->line("  • {$fact}");
        $this->newLine();
    }

    private function displayLogo(): void
    {
        $lines = [
            '  ██╗  ██╗ ███████╗ ██╗  ██╗ ██████╗  ████████╗',
            '  ██║  ██║ ██╔════╝ ██║  ██║ ██╔══██╗ ╚══██╔══╝',
            '  ███████║ █████╗   ███████║ ██████╔╝    ██║   ',
            '  ██╔══██║ ██╔══╝   ╚════██║ ██╔══██╗    ██║   ',
            '  ██║  ██║ ███████╗      ██║ ██║  ██║    ██║   ',
            '  ╚═╝  ╚═╝ ╚══════╝      ╚═╝ ╚═╝  ╚═╝    ╚═╝   ',
        ];

        $this->newLine();

        foreach ($lines as $line) {
            $this->line(sprintf('<fg=%s>%s</>', self::COLOR, $line));
        }

        $this->line(sprintf('  <fg=%s;options=bold> ✦ He4rt Developers :: Antes de começar ✦ </>', self::COLOR));
        $this->newLine();
    }
}
