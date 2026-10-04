<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Health;

use He4rt\IntegrationTwitch\Support\EventSubWebhook;
use He4rt\Streaming\Health\HealthCheck;
use He4rt\Streaming\Health\HealthStatus;
use Illuminate\Support\Str;

final class CheckWebhookAddress
{
    public const string KEY = 'webhook_address';

    private const string TITLE = 'Endereço do webhook';

    private const array LOCAL_SUFFIXES = ['.test', '.local', '.localhost', '.internal'];

    public function handle(): HealthCheck
    {
        if (!EventSubWebhook::isConfigured()) {
            return new HealthCheck(self::KEY, HealthStatus::Error, self::TITLE, 'O segredo do webhook da Twitch não está configurado no servidor.');
        }

        $url = EventSubWebhook::callbackUrl();

        if (!$this->isReachableByTwitch($url)) {
            return new HealthCheck(self::KEY, HealthStatus::Error, self::TITLE, sprintf('A Twitch não alcança %s. O endereço precisa ser HTTPS, público e na porta 443.', $url));
        }

        return new HealthCheck(self::KEY, HealthStatus::Ok, self::TITLE, $url);
    }

    private function isReachableByTwitch(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);

        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && in_array($port, [null, 443], strict: true)
            && is_string($host)
            && !$this->isLocalHost($host);
    }

    private function isLocalHost(string $host): bool
    {
        $isLocalName = $host === 'localhost' || Str::endsWith($host, self::LOCAL_SUFFIXES);
        $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
        $isPublicIp = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;

        return $isLocalName || ($isIp && !$isPublicIp);
    }
}
