<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Transport;

use He4rt\IntegrationSlido\Data\SlidoEvent;
use He4rt\IntegrationSlido\Data\SlidoEventLocation;
use He4rt\IntegrationSlido\Data\SlidoParticipantToken;
use He4rt\IntegrationSlido\Data\SlidoQuestion;
use He4rt\IntegrationSlido\Exceptions\SlidoResponseInvalid;
use He4rt\IntegrationSlido\Transport\Requests\AuthorizeParticipant;
use He4rt\IntegrationSlido\Transport\Requests\ExchangeAuthorizationCode;
use He4rt\IntegrationSlido\Transport\Requests\GetEvent;
use He4rt\IntegrationSlido\Transport\Requests\GetEventByHash;
use He4rt\IntegrationSlido\Transport\Requests\GetQuestions;
use He4rt\IntegrationSlido\Transport\Requests\LookupEvent;
use He4rt\IntegrationSlido\Transport\Requests\RefreshParticipantToken;
use Illuminate\Support\Str;

final readonly class SlidoClient
{
    public function __construct(
        private SlidoLookupConnector $lookupConnector,
        private SlidoAuthConnector $authConnector,
    ) {}

    public function locate(string $eventHash): SlidoEventLocation
    {
        $payload = $this->lookupConnector->send(new LookupEvent($eventHash))->array();

        $uuid = $payload['eventId'] ?? null;
        $cluster = $payload['clusterId'] ?? null;

        throw_unless(is_string($uuid) && is_string($cluster), SlidoResponseInvalid::missing('event lookup', 'eventId, clusterId'));

        return new SlidoEventLocation($eventHash, $uuid, $cluster);
    }

    public function event(SlidoEventLocation $location): SlidoEvent
    {
        $payload = $this->wall($location)->send(new GetEventByHash($location->hash))->array();

        return SlidoEvent::fromPayload($location, $payload);
    }

    public function issueParticipantToken(SlidoEventLocation $location): SlidoParticipantToken
    {
        $codeVerifier = Str::random(64);
        $codeChallenge = mb_rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, binary: true)), '+/', '-_'), '=');

        $authorization = $this->authConnector->send(new AuthorizeParticipant(
            eventUuid: $location->uuid,
            clientId: $this->authConnector->clientId,
            redirectUri: $location->callbackUrl(),
            codeChallenge: $codeChallenge,
        ))->array();

        $code = $this->authorizationCodeOf($authorization);

        $payload = $this->authConnector->send(new ExchangeAuthorizationCode(
            clientId: $this->authConnector->clientId,
            code: $code,
            redirectUri: $location->callbackUrl(),
            codeVerifier: $codeVerifier,
        ))->array();

        return SlidoParticipantToken::fromPayload($payload);
    }

    public function refreshParticipantToken(SlidoParticipantToken $token): SlidoParticipantToken
    {
        $payload = $this->authConnector->send(new RefreshParticipantToken(
            clientId: $this->authConnector->clientId,
            refreshToken: $token->refreshToken,
        ))->array();

        return SlidoParticipantToken::fromPayload($payload);
    }

    /**
     * @return list<SlidoQuestion>
     */
    public function questions(SlidoEventLocation $location, SlidoParticipantToken $token): array
    {
        $wall = $this->wall($location, $token);
        $sectionUuid = $this->activeSectionUuid($wall->send(new GetEvent($location->uuid))->array());

        if ($sectionUuid === null) {
            return [];
        }

        $questions = [];

        foreach ($wall->send(new GetQuestions($location->uuid, $sectionUuid))->array() as $payload) {
            $question = is_array($payload) ? SlidoQuestion::tryFromPayload($payload) : null;

            if ($question instanceof SlidoQuestion && $question->isVisible) {
                $questions[] = $question;
            }
        }

        return $questions;
    }

    private function wall(SlidoEventLocation $location, ?SlidoParticipantToken $token = null): SlidoWallConnector
    {
        return new SlidoWallConnector($location->cluster, $token?->accessToken);
    }

    /**
     * @param  array<array-key, mixed>  $authorization
     */
    private function authorizationCodeOf(array $authorization): string
    {
        $redirectUri = $authorization['redirectUri'] ?? null;

        throw_unless(is_string($redirectUri), SlidoResponseInvalid::missing('authorization', 'redirectUri'));

        parse_str((string) parse_url($redirectUri, PHP_URL_QUERY), $query);

        $code = $query['code'] ?? null;

        throw_unless(is_string($code) && $code !== '', SlidoResponseInvalid::missing('authorization redirect', 'code'));

        return $code;
    }

    /**
     * @param  array<array-key, mixed>  $event
     */
    private function activeSectionUuid(array $event): ?string
    {
        $sections = is_array($event['sections'] ?? null) ? $event['sections'] : [];

        foreach ($sections as $section) {
            $isActiveSection = is_array($section) && ($section['is_active'] ?? false) === true && is_string($section['uuid'] ?? null);

            if ($isActiveSection) {
                return $section['uuid'];
            }
        }

        return null;
    }
}
