<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\DTOs;

use He4rt\Activity\Moderation\Enums\ModerationType;
use Illuminate\Support\Facades\Date;

final readonly class DiscordModerationEventDTO
{
    public function __construct(
        public ModerationType $type,
        public string $botDiscordId,
        public ?string $subjectDiscordId,
        public ?string $subjectUsername,
        public ?string $subjectDiscriminator,
        public ?string $moderatorDiscordId,
        public ?string $reason,
        public string $timestamp,
        /** @var array<string, mixed> */
        public array $metadata,
    ) {}

    /**
     * @param  array<string, mixed>  $message
     */
    public static function fromDump(array $message): ?self
    {
        $author = $message['author'] ?? [];
        if (!($author['bot'] ?? false)) {
            return null;
        }

        if (($author['username'] ?? '') === 'Dyno') {
            return self::fromDynoEmbed($message);
        }

        $embedTitle = $message['embeds'][0]['title'] ?? '';
        if (str_contains($embedTitle, 'Punição')) {
            return self::fromHeartdevsEmbed($message);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public function toDatabase(array $extra = []): array
    {
        $metadata = $this->metadata;

        if ($this->subjectUsername !== null) {
            $metadata['subject_username'] = $this->subjectUsername;
        }

        if ($this->subjectDiscriminator !== null) {
            $metadata['subject_discriminator'] = $this->subjectDiscriminator;
        }

        return [
            'type' => $this->type,
            'reason' => $this->reason,
            'occurred_at' => Date::parse($this->timestamp),
            'metadata' => $metadata,
            ...$extra,
        ];
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private static function fromDynoEmbed(array $message): ?self
    {
        $description = $message['embeds'][0]['description'] ?? '';
        $pattern = '/\*\*\*([^*#]+)#(\d+)\s+(was\s+(?:banned|unbanned|muted|unmuted|kicked)|has\s+been\s+warned)/';

        if (!preg_match($pattern, $description, $m)) {
            return null;
        }

        $action = mb_strtolower($m[3]);
        $type = match (true) {
            str_contains($action, 'was banned') => ModerationType::Ban,
            str_contains($action, 'was unbanned') => ModerationType::Unban,
            str_contains($action, 'was muted') => ModerationType::Mute,
            str_contains($action, 'was unmuted') => ModerationType::Unmute,
            str_contains($action, 'was kicked') => ModerationType::Kick,
            str_contains($action, 'has been warned') => ModerationType::Warn,
            default => null,
        };

        if ($type === null) {
            return null;
        }

        return new self(
            type: $type,
            botDiscordId: (string) $message['author']['id'],
            subjectDiscordId: null,
            subjectUsername: mb_trim($m[1]),
            subjectDiscriminator: $m[2],
            moderatorDiscordId: null,
            reason: null,
            timestamp: $message['timestamp'],
            metadata: $message,
        );
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private static function fromHeartdevsEmbed(array $message): ?self
    {
        $fields = $message['embeds'][0]['fields'] ?? [];
        $data = [];

        foreach ($fields as $field) {
            $name = mb_strtolower($field['name'] ?? '');
            $value = $field['value'] ?? '';
            match (true) {
                str_contains($name, 'usuário punido') => $data['subject'] = $value,
                str_contains($name, 'punido por') => $data['moderator'] = $value,
                str_contains($name, 'tipo') => $data['type'] = $value,
                str_contains($name, 'motivo') => $data['reason'] = $value,
                default => null,
            };
        }

        $type = match (mb_trim($data['type'] ?? '')) {
            'Banimento' => ModerationType::Ban,
            'Desbanimento' => ModerationType::Unban,
            'Silenciamento' => ModerationType::Mute,
            'Advertência' => ModerationType::Warn,
            'Kick' => ModerationType::Kick,
            'Suspensão' => ModerationType::Suspension,
            default => null,
        };

        if ($type === null) {
            return null;
        }

        $subjectId = preg_match('/<@!?(\d+)>/', $data['subject'] ?? '', $sm) ? $sm[1] : null;
        $moderatorId = preg_match('/<@!?(\d+)>/', $data['moderator'] ?? '', $mm) ? $mm[1] : null;

        return new self(
            type: $type,
            botDiscordId: (string) $message['author']['id'],
            subjectDiscordId: $subjectId,
            subjectUsername: null,
            subjectDiscriminator: null,
            moderatorDiscordId: $moderatorId,
            reason: $data['reason'] ?? null,
            timestamp: $message['timestamp'],
            metadata: $message,
        );
    }
}
