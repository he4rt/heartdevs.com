<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Console;

use He4rt\IntegrationGithub\Backfill\BackfillRepository;
use He4rt\IntegrationGithub\Backfill\RateLimit;
use He4rt\IntegrationGithub\Contributions\DTOs\NewContributionDTO;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubRepository;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Saloon\Exceptions\Request\RequestException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Throwable;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

#[Description('Faz backfill do histórico de contribuições dos repositórios da allowlist')]
#[Signature('github:backfill
    {repo? : owner/repo específico. Default: todos os repositórios habilitados}
    {--full : Ignora o last_backfilled_at e varre o histórico inteiro}')]
final class BackfillGithubCommand extends Command
{
    /**
     * Larguras das colunas da tabela de itens (Item · Autor · Quando · Status).
     */
    private const int COL_ITEM = 42;

    private const int COL_AUTHOR = 22;

    private const int COL_WHEN = 20;

    public function handle(BackfillRepository $backfill): int
    {
        $repo = $this->argument('repo');
        $full = (bool) $this->option('full');

        $repositories = is_string($repo)
            ? GithubRepository::query()->where('full_name', $repo)->get()
            : GithubRepository::query()->enabled()->get();

        if ($repositories->isEmpty()) {
            warning('Nenhum repositório para backfill (verifique a allowlist no painel).');

            return self::SUCCESS;
        }

        intro('GitHub Backfill');

        table(
            ['Repositório', 'Último backfill'],
            $repositories->map(fn (GithubRepository $repository): array => [
                $repository->full_name,
                $repository->last_backfilled_at?->format('d/m/Y H:i') ?? 'nunca',
            ])->all(),
        );

        /** @var list<array{0: string, 1: string}> $results */
        $results = [];
        $rateLimited = false;

        foreach ($repositories as $repository) {
            $new = 0;
            $updated = 0;

            $this->newLine();
            $this->line('  <info>'.$repository->full_name.'</info>');

            // Linhas (em cima) e barra (fixa embaixo) em seções independentes: cada
            // item ingerido vira uma linha que rola acima da barra de progresso.
            [$rows, $bar] = $this->liveOutput();
            $rows?->writeln($this->header());

            try {
                $backfill->execute($repository, function (NewContributionDTO $contribution, bool $isNew) use ($rows, $bar, &$new, &$updated): void {
                    $isNew ? $new++ : $updated++;
                    $rows?->writeln($this->row($contribution, $isNew));
                    $bar?->setMessage(sprintf('%d novas · %d atualizadas', $new, $updated));
                    $bar?->advance();
                }, $full);
            } catch (RequestException $exception) {
                $bar?->finish();
                $this->newLine();

                if (RateLimit::matches($exception)) {
                    $results[] = [$repository->full_name, '⏳ rate limit'.RateLimit::resetHint($exception)];
                    $rateLimited = true;

                    break;
                }

                $results[] = [$repository->full_name, '✗ '.$exception->getMessage()];

                continue;
            } catch (Throwable $throwable) {
                $bar?->finish();
                $this->newLine();
                $results[] = [$repository->full_name, '✗ '.$throwable->getMessage()];

                continue;
            }

            $bar?->finish();
            $this->newLine();

            $repository->update(['last_backfilled_at' => Date::now()]);
            $results[] = [$repository->full_name, sprintf('✓ %d novas · %d atualizadas', $new, $updated)];
        }

        table(['Repositório', 'Resultado'], $results);

        if ($rateLimited) {
            warning('Rate limit do GitHub atingido. Os dados já coletados foram salvos; rode novamente após o reset.');

            return self::FAILURE;
        }

        outro('Backfill concluído.');

        return self::SUCCESS;
    }

    /**
     * Monta as duas seções de saída ao vivo: linhas dos itens (em cima) e a barra
     * de progresso (fixa embaixo). Em saída não-TTY (testes, pipes) não há seções —
     * devolve [null, null] e o backfill roda sem feedback visual.
     *
     * @return array{0: ConsoleSectionOutput|null, 1: ProgressBar|null}
     */
    private function liveOutput(): array
    {
        $output = $this->output->getOutput();

        if (!$output instanceof ConsoleOutputInterface) {
            return [null, null];
        }

        $rows = $output->section();
        $bar = new ProgressBar($output->section());
        $bar->setFormat(' %current% processadas  %message%');
        $bar->setMessage('iniciando…');
        $bar->start();

        return [$rows, $bar];
    }

    /**
     * Cabeçalho da tabela de itens, alinhado com as larguras das colunas.
     */
    private function header(): string
    {
        return sprintf(
            '  <fg=gray>%s%s%s%s</>',
            Str::padRight('Item', self::COL_ITEM),
            Str::padRight('Autor', self::COL_AUTHOR),
            Str::padRight('Quando', self::COL_WHEN),
            'Status',
        );
    }

    /**
     * Uma linha da tabela de itens: "Item · Autor · Quando · Status", com o status
     * colorido (verde = nova, amarelo = atualizada) sempre na última coluna para não
     * desalinhar o padding das anteriores.
     */
    private function row(NewContributionDTO $contribution, bool $isNew): string
    {
        $status = $isNew ? '<fg=green>nova</>' : '<fg=yellow>atualizada</>';

        return sprintf(
            '  %s%s%s%s',
            Str::padRight(Str::limit($this->describe($contribution), self::COL_ITEM - 2, '…'), self::COL_ITEM),
            Str::padRight(Str::limit($contribution->actorLogin, self::COL_AUTHOR - 2, '…'), self::COL_AUTHOR),
            Str::padRight($this->humanTime($contribution->occurredAt), self::COL_WHEN),
            $status,
        );
    }

    /**
     * Descrição humana do item, ex.: "PR #42", "issue #10", "commit a1b2c3d",
     * "coment. de review → PR #304". O alvo (target_ref) dá o contexto de PR/issue
     * que o número cru do comentário/review não revela.
     */
    private function describe(NewContributionDTO $contribution): string
    {
        $id = Str::after($contribution->externalRef, ':');
        $target = $this->targetLabel($contribution->targetRef);
        $suffix = $target === '' ? '' : ' → '.$target;

        return match ($contribution->type) {
            ContributionType::Pr => 'PR #'.$id,
            ContributionType::Issue => 'issue #'.$id,
            ContributionType::Commit => 'commit '.Str::limit($id, 7, ''),
            ContributionType::Review => 'review'.$suffix,
            ContributionType::Comment => 'coment.'.$suffix,
            ContributionType::ReviewComment => 'coment. de review'.$suffix,
        };
    }

    /**
     * Traduz um target_ref ("pr:304" / "issue:10") para "PR #304" / "issue #10".
     */
    private function targetLabel(?string $ref): string
    {
        if ($ref === null) {
            return '';
        }

        $id = Str::after($ref, ':');

        return Str::startsWith($ref, 'pr:') ? 'PR #'.$id : 'issue #'.$id;
    }

    /**
     * Tempo relativo do item ("há 3 horas", "há 2 dias") para dar noção de quão
     * recente o backfill alcançou. Datas vêm em ISO-8601 da API do GitHub.
     */
    private function humanTime(string $iso): string
    {
        if ($iso === '') {
            return 'data desconhecida';
        }

        return Date::parse($iso)->settings(['locale' => 'pt_BR'])->diffForHumans();
    }
}
