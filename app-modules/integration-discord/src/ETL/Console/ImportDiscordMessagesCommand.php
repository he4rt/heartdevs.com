<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Console;

use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordMessageAction;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordModerationEventAction;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordReactionsAction;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordVoiceLogAction;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageReactionDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordModerationEventDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordVoiceLogDTO;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Terminal;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

class ImportDiscordMessagesCommand extends Command
{
    protected $signature = 'discord:import-messages
                            {path : Caminho da pasta discord-dump}
                            {--limit= : Para apos importar N mensagens (total)}
                            {--channels= : Lista de nomes (ou substrings) de canais separados por virgula}
                            {--chunks= : Maximo de chunks por canal}';

    protected $description = 'Importa mensagens Discord de um dump completo (messages, reactions, voice, moderation)';

    public function handle(
        ImportDiscordMessageAction $messageAction,
        ImportDiscordReactionsAction $reactionsAction,
        ImportDiscordVoiceLogAction $voiceAction,
        ImportDiscordModerationEventAction $moderationAction,
    ): int {
        DB::disableQueryLog();

        $missingColumns = $this->assertSchema();
        if ($missingColumns !== []) {
            error('Schema desatualizado. Colunas ausentes em "messages": '.implode(', ', $missingColumns));
            error('Rode: php artisan migrate');

            return self::FAILURE;
        }

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

        $channelMap = $this->loadChannelMap($basePath);
        $tenantId = $tenant->getKey();

        $channelDirs = $this->getChannelDirectories($basePath);
        $channelDirs = $this->filterChannels($channelDirs, $this->option('channels'));

        if ($channelDirs === []) {
            error('Nenhum diretorio de canal encontrado.');

            return self::FAILURE;
        }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $chunksPerChannel = $this->option('chunks') !== null ? (int) $this->option('chunks') : null;

        info(sprintf('Importando mensagens de %d canais para tenant "%s"...', count($channelDirs), $tenant->name));
        if ($limit !== null) {
            info(sprintf('Limite total: %s mensagens.', number_format($limit)));
        }

        if ($chunksPerChannel !== null) {
            info(sprintf('Limite por canal: %d chunks.', $chunksPerChannel));
        }

        $stats = ['messages' => 0, 'skipped' => 0, 'reactions' => 0, 'voice' => 0, 'moderation' => 0, 'errors' => 0];
        /** @var list<array{channel:string,message_id:?string,class:string,message:string}> */
        $errorSamples = [];
        /** @var array<string, string> */
        $identityCache = [];

        $output = $this->output->getOutput();

        if (!$output instanceof ConsoleOutputInterface) {
            error('Saida nao suporta sections (ConsoleOutputInterface). Rode em terminal interativo.');

            return self::FAILURE;
        }

        $canalSection = $output->section();
        $chunkSection = $output->section();
        $statsSection = $output->section();

        $totalChannels = count($channelDirs);
        $canalCurrent = 0;
        $lastStatsRender = 0.0;
        $this->renderBox($canalSection, 'Canais', $canalCurrent, $totalChannels);

        foreach ($channelDirs as $channelDir) {
            if ($limit !== null && $stats['messages'] >= $limit) {
                break;
            }

            $channelName = basename($channelDir);
            $chunks = glob($channelDir.'/chunk_*.json');
            sort($chunks);

            if ($chunksPerChannel !== null) {
                $chunks = array_slice($chunks, 0, $chunksPerChannel);
            }

            if ($chunks === []) {
                $canalCurrent++;
                $this->renderBox($canalSection, 'Canais', $canalCurrent, $totalChannels);

                continue;
            }

            $totalChunks = count($chunks);
            $chunkCurrent = 0;
            $chunkTitle = 'Chunks: '.$channelName;
            $this->renderBox($chunkSection, $chunkTitle, $chunkCurrent, $totalChunks);

            foreach ($chunks as $chunkFile) {
                if ($limit !== null && $stats['messages'] >= $limit) {
                    break;
                }

                $allMessages = json_decode(file_get_contents($chunkFile), true);
                if (!is_array($allMessages)) {
                    $chunkCurrent++;
                    $this->renderBox($chunkSection, $chunkTitle, $chunkCurrent, $totalChunks);

                    continue;
                }

                $messages = $this->filterNewMessages($allMessages, $tenantId);
                $stats['skipped'] += count($allMessages) - count($messages);

                if ($messages !== []) {
                    $dtos = array_map(
                        DiscordMessageDTO::fromDump(...),
                        array_values(array_filter($messages, is_array(...))),
                    );

                    if ($limit !== null) {
                        $remaining = $limit - $stats['messages'];
                        $dtos = array_slice($dtos, 0, $remaining);
                    }

                    $identityCache = $messageAction->prewarm($dtos, $tenantId, $identityCache);
                    $replyCache = $messageAction->prewarmReplyTargets($dtos, $tenantId);

                    $rawById = [];
                    foreach ($messages as $raw) {
                        if (is_array($raw) && isset($raw['id'])) {
                            $rawById[(string) $raw['id']] = $raw;
                        }
                    }

                    foreach (array_chunk($dtos, 100) as $dtoBatch) {
                        if ($limit !== null && $stats['messages'] >= $limit) {
                            break;
                        }

                        DB::transaction(function () use (
                            $dtoBatch, $rawById, $messageAction, $reactionsAction, $voiceAction, $moderationAction,
                            $tenantId, $channelMap, $channelName, &$stats, &$errorSamples,
                            $identityCache, $replyCache, $statsSection, &$lastStatsRender,
                        ): void {
                            DB::statement('SET LOCAL synchronous_commit = off');

                            $stubs = $messageAction->handleBatch($dtoBatch, $tenantId, $identityCache, $replyCache);
                            $stats['messages'] += count($stubs);

                            foreach ($stubs as $providerId => $stub) {
                                $raw = $rawById[(string) $providerId] ?? null;
                                if ($raw === null) {
                                    continue;
                                }

                                try {
                                    $this->processSubEntities(
                                        $raw, $stub, $reactionsAction, $voiceAction, $moderationAction,
                                        $tenantId, $channelMap, $stats,
                                    );
                                } catch (Throwable $e) {
                                    $stats['errors']++;
                                    $context = [
                                        'channel' => $channelName,
                                        'message_id' => (string) $providerId,
                                        'class' => $e::class,
                                        'message' => $e->getMessage(),
                                    ];
                                    Log::error('discord-import failed', $context + ['trace' => $e->getTraceAsString()]);
                                    if (count($errorSamples) < 10) {
                                        $errorSamples[] = $context;
                                    }
                                }

                                $now = microtime(true);
                                if ($now - $lastStatsRender > 0.1) {
                                    $this->renderStats($statsSection, $stats);
                                    $lastStatsRender = $now;
                                }
                            }
                        });
                    }
                }

                $chunkCurrent++;
                $this->renderBox($chunkSection, $chunkTitle, $chunkCurrent, $totalChunks);
                $this->renderStats($statsSection, $stats);
                $lastStatsRender = microtime(true);
            }

            $canalCurrent++;
            $this->renderBox($canalSection, 'Canais', $canalCurrent, $totalChannels);
        }

        $this->newLine(2);

        table(
            headers: ['Metrica', 'Quantidade'],
            rows: [
                ['Mensagens importadas', number_format($stats['messages'])],
                ['Mensagens puladas', number_format($stats['skipped'])],
                ['Reactions', number_format($stats['reactions'])],
                ['Voice events', number_format($stats['voice'])],
                ['Moderation events', number_format($stats['moderation'])],
                ['Erros', number_format($stats['errors'])],
            ],
        );

        if ($errorSamples !== []) {
            $this->newLine();
            error(sprintf('Primeiros %d erros (detalhes em storage/logs):', count($errorSamples)));
            table(
                headers: ['Canal', 'Message ID', 'Exception', 'Mensagem'],
                rows: array_map(
                    static fn (array $e): array => [
                        $e['channel'],
                        $e['message_id'] ?? '-',
                        class_basename($e['class']),
                        mb_substr($e['message'], 0, 120),
                    ],
                    $errorSamples,
                ),
            );
        }

        info('Importacao finalizada.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, string>  $channelMap
     * @param  array<string, int>  $stats
     */
    private function processSubEntities(
        array $raw,
        Message $message,
        ImportDiscordReactionsAction $reactionsAction,
        ImportDiscordVoiceLogAction $voiceAction,
        ImportDiscordModerationEventAction $moderationAction,
        int $tenantId,
        array $channelMap,
        array &$stats,
    ): void {
        $reactions = DiscordMessageReactionDTO::fromDumpMessage($raw);
        if ($reactions !== []) {
            $reactionsAction->handle($message, $reactions, $tenantId);
            $stats['reactions'] += count($reactions);
        }

        $voiceDto = DiscordVoiceLogDTO::fromDump($raw);
        if ($voiceDto instanceof DiscordVoiceLogDTO) {
            $voice = $voiceAction->handle($voiceDto, $tenantId, $channelMap);
            if ($voice instanceof Voice) {
                $stats['voice']++;
            }
        }

        $moderationDto = DiscordModerationEventDTO::fromDump($raw);
        if ($moderationDto instanceof DiscordModerationEventDTO) {
            $moderationAction->handle($moderationDto, $tenantId, $message->id);
            $stats['moderation']++;
        }
    }

    /**
     * @return list<string>
     */
    private function assertSchema(): array
    {
        $required = [
            'id', 'tenant_id', 'external_identity_id', 'provider_message_id',
            'channel_id', 'content', 'metadata', 'sent_at', 'edited_at',
            'kind', 'raw_message_type', 'source_kind', 'is_pinned',
            'mentions_everyone', 'mention_role_count', 'obtained_experience',
            'reply_to_provider_message_id', 'reply_to_message_id',
            'created_at', 'updated_at',
        ];

        $existing = array_flip(Schema::getColumnListing('messages'));

        return array_values(array_filter(
            $required,
            static fn (string $col): bool => !isset($existing[$col]),
        ));
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function renderStats(ConsoleSectionOutput $section, array $stats): void
    {
        $section->overwrite(sprintf(
            '   Msgs: %s | Skip: %s | React: %s | Voice: %s | Mod: %s',
            number_format($stats['messages']),
            number_format($stats['skipped']),
            number_format($stats['reactions']),
            number_format($stats['voice']),
            number_format($stats['moderation']),
        ));
    }

    private function renderBox(ConsoleSectionOutput $section, string $title, int $current, int $max): void
    {
        $cols = (new Terminal())->getWidth();
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
     * @param  list<array<string, mixed>>  $messages
     * @return list<array<string, mixed>>
     */
    private function filterNewMessages(array $messages, int $tenantId): array
    {
        $unique = [];
        foreach ($messages as $m) {
            if (is_array($m) && isset($m['id'])) {
                $unique[(string) $m['id']] = $m;
            }
        }

        if ($unique === []) {
            return [];
        }

        $existing = Message::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('provider_message_id', array_keys($unique))
            ->pluck('provider_message_id')
            ->flip()
            ->all();

        return array_values(array_filter(
            $unique,
            static fn (array $m): bool => !isset($existing[$m['id']]),
        ));
    }

    /**
     * @return array<string, string>
     */
    private function loadChannelMap(string $basePath): array
    {
        $channelsFile = $basePath.'/channels.json';
        if (!file_exists($channelsFile)) {
            return [];
        }

        $payload = json_decode(file_get_contents($channelsFile), true);
        $channels = is_array($payload) ? ($payload['channels'] ?? $payload) : [];
        $map = [];

        foreach ($channels as $channel) {
            if (!is_array($channel) || !isset($channel['id'])) {
                continue;
            }

            $map[(string) $channel['id']] = $channel['name'] ?? (string) $channel['id'];
        }

        return $map;
    }

    /**
     * @param  list<string>  $channelDirs
     * @return list<string>
     */
    private function filterChannels(array $channelDirs, ?string $filter): array
    {
        if ($filter === null || mb_trim($filter) === '') {
            return $channelDirs;
        }

        $needles = array_values(array_filter(array_map(trim(...), explode(',', $filter))));

        if ($needles === []) {
            return $channelDirs;
        }

        return array_values(array_filter(
            $channelDirs,
            static function (string $dir) use ($needles): bool {
                $name = basename($dir);
                foreach ($needles as $needle) {
                    if (str_contains($name, $needle)) {
                        return true;
                    }
                }

                return false;
            },
        ));
    }

    /**
     * @return list<string>
     */
    private function getChannelDirectories(string $basePath): array
    {
        $dirs = [];

        foreach (scandir($basePath) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $basePath.'/'.$entry;
            if (is_dir($fullPath) && file_exists($fullPath.'/_resume.json')) {
                $dirs[] = $fullPath;
            }
        }

        sort($dirs);

        return $dirs;
    }
}
