<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Console;

use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordProfileAction;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordProfileDTO;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Terminal;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

#[Description('Importa perfis Discord de todos os chunks JSON para Users e ExternalIdentities')]
#[Signature('discord:import-profiles
                            {path : Caminho do diretorio com os chunks JSON}
                            {--from=0 : Numero do chunk inicial (ex: 5 para comecar do chunk_5)}')]
class ImportDiscordProfilesCommand extends Command
{
    public function handle(ImportDiscordProfileAction $action): int
    {
        DB::disableQueryLog();

        $missingColumns = $this->assertSchema();
        if ($missingColumns !== []) {
            error('Schema desatualizado. Colunas ausentes: '.implode(', ', $missingColumns));
            error('Rode: php artisan migrate');

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

        $stats = ['created' => 0, 'skipped' => 0, 'errors' => 0];
        /** @var list<array{chunk: string, discord_id: string, username: string, error: string}> */
        $errorSamples = [];

        info(sprintf('Encontrados %d chunks para importar.', count($chunks)));

        $output = $this->output->getOutput();

        if (!$output instanceof ConsoleOutputInterface) {
            error('Saida nao suporta sections (ConsoleOutputInterface). Rode em terminal interativo.');

            return self::FAILURE;
        }

        $chunkSection = $output->section();
        $profileSection = $output->section();
        $statsSection = $output->section();

        $totalChunks = count($chunks);
        $chunkCurrent = 0;
        $lastStatsRender = 0.0;
        $this->renderBox($chunkSection, 'Chunks', $chunkCurrent, $totalChunks);

        foreach ($chunks as $chunkFile) {
            $chunkName = basename($chunkFile);
            $chunkContents = file_get_contents($chunkFile);
            throw_if($chunkContents === false, RuntimeException::class, 'Falha ao ler chunk: '.$chunkFile);

            $profiles = json_decode($chunkContents, associative: true);

            if (!is_array($profiles) || $profiles === []) {
                $chunkCurrent++;
                $this->renderBox($chunkSection, 'Chunks', $chunkCurrent, $totalChunks);

                continue;
            }

            $totalProfiles = count($profiles);
            $profileCurrent = 0;
            $profileTitle = 'Perfis: '.$chunkName;
            $profileSection->clear();
            $this->renderBox($profileSection, $profileTitle, $profileCurrent, $totalProfiles);

            foreach (array_chunk($profiles, 250) as $batch) {
                DB::transaction(function () use (
                    $batch, $action, $chunkName,
                    &$stats, &$errorSamples, &$profileCurrent, $totalProfiles,
                    $profileSection, $statsSection, $profileTitle, &$lastStatsRender,
                ): void {
                    foreach ($batch as $profile) {
                        $dto = DiscordProfileDTO::fromDump($profile);

                        try {
                            $identity = $action->handle($dto);
                            $identity->wasRecentlyCreated ? $stats['created']++ : $stats['skipped']++;
                        } catch (Throwable $e) {
                            $stats['errors']++;
                            $context = [
                                'chunk' => $chunkName,
                                'discord_id' => $dto->discordId,
                                'username' => $dto->username,
                                'error' => $e->getMessage(),
                            ];
                            Log::error('discord-profile-import failed', $context + ['trace' => $e->getTraceAsString()]);
                            if (count($errorSamples) < 20) {
                                $errorSamples[] = $context;
                            }
                        }

                        $profileCurrent++;

                        $now = microtime(as_float: true);
                        if ($now - $lastStatsRender > 0.1) {
                            $this->renderBox($profileSection, $profileTitle, $profileCurrent, $totalProfiles);
                            $this->renderStats($statsSection, $stats);
                            $lastStatsRender = $now;
                        }
                    }
                });
            }

            $this->renderBox($profileSection, $profileTitle, $profileCurrent, $totalProfiles);
            $this->renderStats($statsSection, $stats);
            $lastStatsRender = microtime(as_float: true);

            $chunkCurrent++;
            $this->renderBox($chunkSection, 'Chunks', $chunkCurrent, $totalChunks);
        }

        $this->newLine(2);

        table(
            headers: ['Metrica', 'Quantidade'],
            rows: [
                ['Chunks processados', (string) count($chunks)],
                ['Perfis criados', number_format($stats['created'])],
                ['Perfis pulados', number_format($stats['skipped'])],
                ['Total processados', number_format($stats['created'] + $stats['skipped'] + $stats['errors'])],
                ['Erros', number_format($stats['errors'])],
            ],
        );

        if ($errorSamples !== []) {
            $this->newLine();
            error(sprintf('Primeiros %d erros (detalhes em storage/logs):', count($errorSamples)));
            table(
                headers: ['Chunk', 'Discord ID', 'Username', 'Erro'],
                rows: array_map(
                    static fn (array $e): array => [$e['chunk'], $e['discord_id'], $e['username'], mb_substr($e['error'], 0, 120)],
                    $errorSamples,
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
    private function assertSchema(): array
    {
        $required = [
            'external_identities' => [
                'id', 'provider', 'external_account_id', 'type',
                'model_type', 'model_id', 'credentials_type', 'credentials',
                'connected_at', 'metadata', 'created_at', 'updated_at',
            ],
            'users' => [
                'id', 'username', 'name', 'is_donator', 'created_at', 'updated_at',
            ],
        ];

        $missing = [];
        foreach ($required as $table => $cols) {
            /** @var array<int, string> $columnList */
            $columnList = Schema::getColumnListing($table);
            $existing = array_flip($columnList);
            foreach ($cols as $col) {
                if (!isset($existing[$col])) {
                    $missing[] = $table.'.'.$col;
                }
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function renderStats(ConsoleSectionOutput $section, array $stats): void
    {
        $section->overwrite(sprintf(
            '   Criados: %s | Pulados: %s | Erros: %s',
            number_format($stats['created']),
            number_format($stats['skipped']),
            number_format($stats['errors']),
        ));
    }

    private function renderBox(ConsoleSectionOutput $section, string $title, int $current, int $max): void
    {
        $cols = new Terminal()->getWidth();
        $width = max(20, min(60, $cols - 6));

        $title = mb_strimwidth($title, 0, $width - 2, '...');
        $titleLen = mb_strwidth($title);
        $topDashes = str_repeat('─', max(0, $width - $titleLen));

        $pct = $max > 0 ? min(1.0, $current / $max) : 0.0;
        $filled = (int) ceil($pct * $width);
        $bar = str_repeat('█', $filled);
        $body = $bar.str_repeat(' ', max(0, $width - mb_strwidth($bar)));

        $info = number_format($current).' / '.number_format($max);
        $infoLen = mb_strwidth($info);
        $bottomDashes = str_repeat('─', max(0, $width - $infoLen));

        $section->overwrite(implode("\n", [
            ' ┌ '.$title.' '.$topDashes.'┐',
            ' │ '.$body.' │',
            ' └'.$bottomDashes.' '.$info.' ┘',
        ]));
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
