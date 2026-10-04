<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming;

use Carbon\CarbonInterface;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Clusters\Streaming\Enums\StreamAlertType;

/**
 * Dados de exemplo da área "Minha Live", até existir o modelo de overlay e alertas.
 */
final class StreamingPreviewData
{
    /**
     * @return array<int, array{type: StreamAlertType, value: string}>
     */
    public static function stats(): array
    {
        return [
            ['type' => StreamAlertType::Follow, 'value' => '128'],
            ['type' => StreamAlertType::Sub, 'value' => '14'],
            ['type' => StreamAlertType::Cheer, 'value' => '3.250'],
            ['type' => StreamAlertType::Raid, 'value' => '3'],
        ];
    }

    /**
     * @return array<int, array{type: StreamAlertType, username: string, detail: string, at: CarbonInterface}>
     */
    public static function recentActivity(): array
    {
        return [
            ['type' => StreamAlertType::Follow, 'username' => 'devlucasdev', 'detail' => '', 'at' => now()->subMinutes(2)],
            ['type' => StreamAlertType::Cheer, 'username' => 'mariacoda', 'detail' => '500 bits', 'at' => now()->subMinutes(9)],
            ['type' => StreamAlertType::Sub, 'username' => 'pedroterminal', 'detail' => '3 meses', 'at' => now()->subMinutes(25)],
            ['type' => StreamAlertType::Raid, 'username' => 'canaldoze', 'detail' => '+42 viewers', 'at' => now()->subHour()],
            ['type' => StreamAlertType::GiftSub, 'username' => 'anabackend', 'detail' => '5 subs', 'at' => now()->subHours(3)],
        ];
    }

    public static function overlayToken(User $user): string
    {
        return hash('xxh128', 'overlay-preview:'.$user->getKey());
    }
}
