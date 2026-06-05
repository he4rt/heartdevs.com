<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Console;

use He4rt\IntegrationGithub\Backfill\BackfillRepository;
use He4rt\IntegrationGithub\Backfill\RateLimit;
use He4rt\IntegrationGithub\Models\GithubRepository;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Saloon\Exceptions\Request\RequestException;
use Throwable;

#[Description('Faz backfill do histórico de contribuições dos repositórios da allowlist')]
#[Signature('github:backfill
    {repo? : owner/repo específico. Default: todos os repositórios habilitados}')]
final class BackfillGithubCommand extends Command
{
    public function handle(BackfillRepository $backfill): int
    {
        $repo = $this->argument('repo');

        $repositories = is_string($repo)
            ? GithubRepository::query()->where('full_name', $repo)->get()
            : GithubRepository::query()->enabled()->get();

        if ($repositories->isEmpty()) {
            $this->warn('Nenhum repositório para backfill (verifique a allowlist no painel).');

            return self::SUCCESS;
        }

        foreach ($repositories as $repository) {
            $this->info(sprintf('Backfilling %s...', $repository->full_name));

            try {
                $backfill->execute($repository->full_name);
            } catch (RequestException $exception) {
                if (RateLimit::matches($exception)) {
                    $this->warn(sprintf(
                        'Rate limit do GitHub atingido em %s. Os dados já coletados foram salvos; rode novamente após o reset%s.',
                        $repository->full_name,
                        RateLimit::resetHint($exception),
                    ));

                    return self::FAILURE;
                }

                $this->error(sprintf('Falha em %s: %s', $repository->full_name, $exception->getMessage()));

                continue;
            } catch (Throwable $throwable) {
                $this->error(sprintf('Falha em %s: %s', $repository->full_name, $throwable->getMessage()));

                continue;
            }

            $repository->update(['last_backfilled_at' => Date::now()]);
        }

        $this->info('Backfill concluído.');

        return self::SUCCESS;
    }
}
