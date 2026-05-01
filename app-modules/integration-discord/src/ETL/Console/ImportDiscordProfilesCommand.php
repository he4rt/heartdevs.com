<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Console;

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordProfileAction;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordProfileDTO;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\table;

class ImportDiscordProfilesCommand extends Command
{
    protected $signature = 'discord:import-profiles
                            {path : Caminho do diretorio com os chunks JSON}
                            {--from=0 : Numero do chunk inicial (ex: 5 para comecar do chunk_5)}';

    protected $description = 'Importa perfis Discord de todos os chunks JSON para Users e ExternalIdentities';

    public function handle(ImportDiscordProfileAction $action): int
    {
        $tenant = Tenant::query()->where('slug', 'he4rt')->first();

        if (!$tenant) {
            error('Tenant "he4rt" nao encontrado.');

            return self::FAILURE;
        }

        $basePath = $this->argument('path');

        if (!is_dir($basePath)) {
            error('Diretorio nao encontrado: '.$basePath);

            return self::FAILURE;
        }

        $chunks = $this->discoverChunks($basePath);

        if ($chunks === []) {
            error('Nenhum arquivo profiles_chunk_*.json encontrado em: '.$basePath);

            return self::FAILURE;
        }

        $from = (int) $this->option('from');
        if ($from > 0) {
            $chunks = array_values(array_filter(
                $chunks,
                static fn (string $file): bool => self::extractChunkNumber($file) >= $from,
            ));
        }

        $tenantId = $tenant->getKey();
        $created = 0;
        $skipped = 0;
        /** @var list<array{chunk: string, discord_id: string, username: string, error: string}> */
        $errors = [];

        info(sprintf('Encontrados %d chunks para importar no tenant "%s".', count($chunks), $tenant->name));

        progress(
            label: 'Importando chunks de perfis',
            steps: $chunks,
            callback: function (string $chunkFile, $progress) use ($action, $tenantId, &$created, &$skipped, &$errors): void {
                $chunkName = basename($chunkFile);
                $progress->label('Chunk: '.$chunkName);

                $profiles = json_decode(file_get_contents($chunkFile), true);

                if (!is_array($profiles) || $profiles === []) {
                    $progress->hint($chunkName.' — vazio ou invalido, pulando');

                    return;
                }

                foreach ($profiles as $profile) {
                    $dto = DiscordProfileDTO::fromDump($profile);

                    try {
                        $identity = $action->handle($dto, $tenantId);
                        $identity->wasRecentlyCreated ? $created++ : $skipped++;
                    } catch (Throwable $throwable) {
                        $errors[] = [
                            'chunk' => $chunkName,
                            'discord_id' => $dto->discordId,
                            'username' => $dto->username,
                            'error' => $throwable->getMessage(),
                        ];

                        Log::error('discord-profile-import failed', [
                            'chunk' => $chunkName,
                            'discord_id' => $dto->discordId,
                            'message' => $throwable->getMessage(),
                        ]);
                    }
                }

                $progress->hint(sprintf(
                    'Criados: %s | Pulados: %s | Erros: %s',
                    number_format($created),
                    number_format($skipped),
                    number_format(count($errors)),
                ));
            },
            hint: 'Isso pode levar bastante tempo...',
        );

        $this->newLine();

        table(
            headers: ['Metrica', 'Quantidade'],
            rows: [
                ['Chunks processados', (string) count($chunks)],
                ['Perfis criados', number_format($created)],
                ['Perfis pulados', number_format($skipped)],
                ['Total processados', number_format($created + $skipped + count($errors))],
                ['Erros', number_format(count($errors))],
            ],
        );

        if ($errors !== []) {
            $this->newLine();
            $displayErrors = array_slice($errors, 0, 20);
            error(sprintf('Primeiros %d erros (detalhes em storage/logs):', count($displayErrors)));
            table(
                headers: ['Chunk', 'Discord ID', 'Username', 'Erro'],
                rows: array_map(
                    static fn (array $e): array => [$e['chunk'], $e['discord_id'], $e['username'], mb_substr($e['error'], 0, 120)],
                    $displayErrors,
                ),
            );
        }

        info('Importacao finalizada.');

        return self::SUCCESS;
    }

    private static function extractChunkNumber(string $filename): int
    {
        preg_match('/profiles_chunk_(\d+)\.json$/', $filename, $matches);

        return (int) ($matches[1] ?? 0);
    }

    /**
     * @return list<string>
     */
    private function discoverChunks(string $basePath): array
    {
        $files = glob($basePath.'/profiles_chunk_*.json');

        if ($files === false || $files === []) {
            return [];
        }

        usort($files, static fn (string $a, string $b): int => self::extractChunkNumber($a) <=> self::extractChunkNumber($b));

        return $files;
    }
}
