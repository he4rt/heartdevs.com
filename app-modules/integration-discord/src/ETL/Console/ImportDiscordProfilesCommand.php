<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Console;

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordProfileAction;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordProfileDTO;
use Illuminate\Console\Command;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\table;

class ImportDiscordProfilesCommand extends Command
{
    protected $signature = 'discord:import-profiles
                            {file : Caminho do arquivo JSON}';

    protected $description = 'Importa perfis Discord de um dump JSON para Users e ExternalIdentities';

    public function handle(ImportDiscordProfileAction $action): int
    {
        $tenant = Tenant::query()->where('slug', 'he4rt')->first();

        if (!$tenant) {
            error('Tenant "he4rt" nao encontrado.');

            return self::FAILURE;
        }

        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            error('Arquivo nao encontrado: '.$filePath);

            return self::FAILURE;
        }

        $profiles = json_decode(file_get_contents($filePath), true);

        if (!is_array($profiles) || $profiles === []) {
            error('Arquivo JSON invalido ou vazio.');

            return self::FAILURE;
        }

        info(sprintf('Importando %d perfis para o tenant "%s"...', count($profiles), $tenant->name));

        $created = 0;
        $updated = 0;
        $errors = [];

        progress(
            label: 'Importando perfis Discord',
            steps: $profiles,
            callback: function (array $profile, $progress) use ($action, $tenant, &$created, &$updated, &$errors): void {
                $dto = DiscordProfileDTO::fromDump($profile);

                try {
                    $identity = $action->handle($dto, $tenant->getKey());
                    $identity->wasRecentlyCreated ? $created++ : $updated++;

                    $progress
                        ->label('Processando: '.$dto->username)
                        ->hint('Discord ID: '.$dto->discordId);
                } catch (Throwable $throwable) {
                    $errors[] = [
                        'discord_id' => $dto->discordId,
                        'username' => $dto->username,
                        'error' => $throwable->getMessage(),
                    ];

                    $progress->hint('Erro: '.$dto->discordId);
                }
            },
            hint: 'Isso pode levar alguns minutos...',
        );

        $this->newLine();

        table(
            headers: ['Metrica', 'Quantidade'],
            rows: [
                ['Total processados', (string) count($profiles)],
                ['Criados', (string) $created],
                ['Atualizados', (string) $updated],
                ['Erros', (string) count($errors)],
            ],
        );

        if ($errors !== []) {
            $this->newLine();
            error('Perfis com erro:');
            table(
                headers: ['Discord ID', 'Username', 'Erro'],
                rows: array_map(fn (array $e) => [$e['discord_id'], $e['username'], $e['error']], $errors),
            );
        }

        info('Importacao finalizada.');

        return self::SUCCESS;
    }
}
