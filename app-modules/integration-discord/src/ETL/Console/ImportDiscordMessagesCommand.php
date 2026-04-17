<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Console;

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
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\table;

class ImportDiscordMessagesCommand extends Command
{
    protected $signature = 'discord:import-messages
                            {path : Caminho da pasta discord-dump}';

    protected $description = 'Importa mensagens Discord de um dump completo (messages, reactions, voice, moderation)';

    public function handle(
        ImportDiscordMessageAction $messageAction,
        ImportDiscordReactionsAction $reactionsAction,
        ImportDiscordVoiceLogAction $voiceAction,
        ImportDiscordModerationEventAction $moderationAction,
    ): int {
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

        if ($channelDirs === []) {
            error('Nenhum diretorio de canal encontrado.');

            return self::FAILURE;
        }

        info(sprintf('Importando mensagens de %d canais para tenant "%s"...', count($channelDirs), $tenant->name));

        $stats = ['messages' => 0, 'reactions' => 0, 'voice' => 0, 'moderation' => 0, 'errors' => 0];

        progress(
            label: 'Importando canais',
            steps: $channelDirs,
            callback: function (string $channelDir, $progress) use (
                $messageAction, $reactionsAction, $voiceAction, $moderationAction,
                $tenantId, $channelMap, &$stats,
            ): void {
                $channelName = basename($channelDir);
                $progress->label('Canal: '.$channelName);

                $chunks = glob($channelDir.'/chunk_*.json');
                sort($chunks);

                foreach ($chunks as $chunkFile) {
                    $messages = json_decode(file_get_contents($chunkFile), true);
                    if (!is_array($messages)) {
                        continue;
                    }

                    DB::transaction(function () use (
                        $messages, $messageAction, $reactionsAction, $voiceAction, $moderationAction,
                        $tenantId, $channelMap, &$stats,
                    ): void {
                        foreach ($messages as $raw) {
                            try {
                                $this->processMessage(
                                    $raw, $messageAction, $reactionsAction, $voiceAction, $moderationAction,
                                    $tenantId, $channelMap, $stats,
                                );
                            } catch (Throwable) {
                                $stats['errors']++;
                            }
                        }
                    });

                    $progress->hint(sprintf(
                        'Msgs: %s | React: %s | Voice: %s | Mod: %s',
                        number_format($stats['messages']),
                        number_format($stats['reactions']),
                        number_format($stats['voice']),
                        number_format($stats['moderation']),
                    ));
                }
            },
            hint: 'Isso pode levar bastante tempo...',
        );

        $this->newLine();

        table(
            headers: ['Metrica', 'Quantidade'],
            rows: [
                ['Mensagens', number_format($stats['messages'])],
                ['Reactions', number_format($stats['reactions'])],
                ['Voice events', number_format($stats['voice'])],
                ['Moderation events', number_format($stats['moderation'])],
                ['Erros', number_format($stats['errors'])],
            ],
        );

        info('Importacao finalizada.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, string>  $channelMap
     * @param  array<string, int>  $stats
     */
    private function processMessage(
        array $raw,
        ImportDiscordMessageAction $messageAction,
        ImportDiscordReactionsAction $reactionsAction,
        ImportDiscordVoiceLogAction $voiceAction,
        ImportDiscordModerationEventAction $moderationAction,
        int $tenantId,
        array $channelMap,
        array &$stats,
    ): void {
        $dto = DiscordMessageDTO::fromDump($raw);
        $message = $messageAction->handle($dto, $tenantId);
        $stats['messages']++;

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
     * @return array<string, string>
     */
    private function loadChannelMap(string $basePath): array
    {
        $channelsFile = $basePath.'/channels.json';
        if (!file_exists($channelsFile)) {
            return [];
        }

        $channels = json_decode(file_get_contents($channelsFile), true);
        $map = [];

        foreach ($channels as $channel) {
            $map[$channel['id']] = $channel['name'] ?? $channel['id'];
        }

        return $map;
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
