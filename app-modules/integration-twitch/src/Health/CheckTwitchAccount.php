<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Health;

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\IntegrationTwitch\Exceptions\TwitchUnreachable;
use He4rt\IntegrationTwitch\OAuth\TwitchUserAuthorization;
use He4rt\Streaming\Health\HealthCheck;
use He4rt\Streaming\Health\HealthFix;
use He4rt\Streaming\Health\HealthStatus;

final readonly class CheckTwitchAccount
{
    public const string KEY = 'twitch_account';

    private const string TITLE = 'Conta da Twitch';

    public function __construct(
        private TwitchUserAuthorization $authorization,
    ) {}

    /**
     * @param  array<int, string>  $requiredScopes
     */
    public function handle(ExternalIdentity $identity, array $requiredScopes): HealthCheck
    {
        try {
            $grantedScopes = $this->authorization->grantedScopes($identity);
        } catch (TwitchUnreachable) {
            return new HealthCheck(self::KEY, HealthStatus::Warning, self::TITLE, 'A Twitch não respondeu agora. Verifique de novo em instantes.');
        }

        if ($grantedScopes === null) {
            return new HealthCheck(self::KEY, HealthStatus::Error, self::TITLE, 'A Twitch recusou a autorização. Conecte de novo para os eventos voltarem.', HealthFix::Reconnect);
        }

        $required = count($requiredScopes);
        $missing = count(array_diff($requiredScopes, $grantedScopes));

        if ($missing > 0) {
            return new HealthCheck(self::KEY, HealthStatus::Warning, self::TITLE, sprintf('Faltam %d de %d permissões.', $missing, $required), HealthFix::Reconnect);
        }

        return new HealthCheck(self::KEY, HealthStatus::Ok, self::TITLE, sprintf('Autorização válida, %d de %d permissões.', $required, $required));
    }
}
