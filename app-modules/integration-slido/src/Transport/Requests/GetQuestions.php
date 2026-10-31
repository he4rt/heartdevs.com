<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Transport\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetQuestions extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $eventUuid,
        private readonly string $sectionUuid,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/events/%s/questions', rawurlencode($this->eventUuid));
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return [
            'eventSectionId' => $this->sectionUuid,
            'highlighted_first' => 'true',
            'path' => '/questions',
            'sort' => 'top',
        ];
    }
}
