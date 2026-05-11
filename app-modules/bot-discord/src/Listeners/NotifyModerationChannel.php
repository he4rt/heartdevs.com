<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Listeners;

use He4rt\Moderation\Cases\Events\CaseCreated;
use He4rt\Moderation\Cases\Models\ModerationCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class NotifyModerationChannel
{
    public function handle(CaseCreated $event): void
    {
        $token = config('discord.token', config('he4rt.discord.token'));
        $channelId = config()->string('he4rt.discord.moderation.mod_channel_id');

        if (!is_string($token) || blank($token) || blank($channelId)) {
            return;
        }

        $case = $event->case;

        $response = Http::withHeaders(['Authorization' => 'Bot '.$token])
            ->post(
                sprintf('https://discord.com/api/v10/channels/%s/messages', $channelId),
                ['embeds' => [$this->buildEmbed($case)]],
            );

        if ($response->failed()) {
            Log::warning('Failed to notify mod channel about new moderation case.', [
                'case_id' => $case->id,
                'status' => $response->status(),
                'response' => $response->json() ?: $response->body(),
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function buildEmbed(ModerationCase $case): array
    {
        $author = $case->content_snapshot['metadata']['username'] ?? 'Unknown';
        $text = $case->content_snapshot['text'] ?? null;
        $platform = $case->source_platform->value;

        $fields = [
            ['name' => 'Platform', 'value' => ucfirst($platform), 'inline' => true],
            ['name' => 'Source', 'value' => $case->source->value, 'inline' => true],
            ['name' => 'Priority', 'value' => (string) $case->priority, 'inline' => true],
        ];

        if ($text !== null) {
            $fields[] = [
                'name' => 'Content',
                'value' => mb_substr($text, 0, 1024),
                'inline' => false,
            ];
        }

        return [
            'title' => sprintf(':warning: New moderation case — %s', $author),
            'description' => sprintf('Case ID: `%s`', $case->id),
            'color' => 0xFFA500,
            'fields' => $fields,
            'timestamp' => $case->created_at->toIso8601String(),
            'footer' => ['text' => 'He4rt Moderation System'],
        ];
    }
}
