<?php

declare(strict_types=1);

namespace App\Providers\Tools;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use ReflectionClass;
use Spatie\Backup\BackupServiceProvider;
use Spatie\Backup\Notifications\Channels\Discord\DiscordChannel;
use Spatie\Backup\Notifications\Channels\Webhook\WebhookChannel;

final class SpatieBackupServiceProvider extends BackupServiceProvider
{
    protected function registerNotificationChannels(): void
    {
        Notification::resolved(function (ChannelManager $service): void {
            $service->extend('spatie.backup.discord', fn ($app): DiscordChannel => new DiscordChannel);
            $service->extend('spatie.backup.webhook', fn ($app): WebhookChannel => new WebhookChannel);
        });
    }

    protected function getPackageBaseDir(): string
    {
        $reflector = new ReflectionClass(BackupServiceProvider::class);

        return dirname($reflector->getFileName());
    }
}
