<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Console;

use He4rt\IntegrationGithub\Backfill\BackfillRepository;
use He4rt\IntegrationGithub\Backfill\RateLimit;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubRepository;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Saloon\Exceptions\Request\RequestException;
use Throwable;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

#[Description('Faz backfill do histórico de contribuições dos repositórios da allowlist')]
#[Signature('github:backfill
    {repo? : owner/repo específico. Default: todos os repositórios habilitados}')]
final class BackfillGithubCommand extends Command
{
    /**
     * Rótulos curtos por tipo, na ordem em que aparecem no contador ao vivo.
     */
    private const array LABELS = [
        'pr' => 'PRs',
        'review' => 'reviews',
        'issue' => 'issues',
        'comment' => 'coment.',
        'review_comment' => 'coment.review',
        'commit' => 'commits',
    ];

    public function handle(BackfillRepository $backfill): int
    {
        $repo = $this->argument('repo');

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
            /** @var array<string, int> $counts */
            $counts = [];

            $this->line('  <info>'.$repository->full_name.'</info>');
            $bar = $this->output->createProgressBar();
            $bar->setFormat(' %current% ingeridas  %message%');
            $bar->setMessage('');
            $bar->start();

            try {
                $backfill->execute($repository, function (ContributionType $type) use ($bar, &$counts): void {
                    $counts[$type->value] = ($counts[$type->value] ?? 0) + 1;
                    $bar->setMessage($this->tally($counts));
                    $bar->advance();
                });
            } catch (RequestException $exception) {
                $bar->finish();
                $this->newLine(2);

                if (RateLimit::matches($exception)) {
                    $results[] = [$repository->full_name, '⏳ rate limit'.RateLimit::resetHint($exception)];
                    $rateLimited = true;

                    break;
                }

                $results[] = [$repository->full_name, '✗ '.$exception->getMessage()];

                continue;
            } catch (Throwable $throwable) {
                $bar->finish();
                $this->newLine(2);
                $results[] = [$repository->full_name, '✗ '.$throwable->getMessage()];

                continue;
            }

            $bar->finish();
            $this->newLine(2);

            $repository->update(['last_backfilled_at' => Date::now()]);
            $results[] = [$repository->full_name, '✓ '.array_sum($counts).' contribuições'];
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
     * Contador por tipo, ex.: "PRs 61 · reviews 980 · issues 44".
     *
     * @param  array<string, int>  $counts
     */
    private function tally(array $counts): string
    {
        $parts = [];

        foreach (self::LABELS as $key => $label) {
            if (($counts[$key] ?? 0) > 0) {
                $parts[] = $label.' '.$counts[$key];
            }
        }

        return implode(' · ', $parts);
    }
}
