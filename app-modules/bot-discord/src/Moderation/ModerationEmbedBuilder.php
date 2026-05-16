<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Moderation;

use He4rt\Moderation\Cases\Models\ModerationCase;

final class ModerationEmbedBuilder
{
    /** @return array<string, mixed> */
    public function buildCaseEmbed(ModerationCase $case): array
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

    public function buildRoleMentions(): string
    {
        /** @var array<int, string> $adminRoles */
        $adminRoles = config('he4rt.discord.moderation.admin_role_ids', []);

        /** @var array<int, string> $modRoles */
        $modRoles = config('he4rt.discord.moderation.mod_role_ids', []);

        $mentions = array_map(
            fn (string $id): string => sprintf('<@&%s>', $id),
            array_merge($adminRoles, $modRoles),
        );

        return implode(' ', $mentions);
    }
}
