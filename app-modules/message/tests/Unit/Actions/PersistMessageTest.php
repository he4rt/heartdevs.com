<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Message\Actions\PersistMessage;
use He4rt\Message\Contracts\MessageRepository;
use He4rt\Message\DTO\NewMessageDTO;
use He4rt\Message\Tests\Unit\MessageProviderTrait;

uses(MessageProviderTrait::class);

beforeEach(function (): void {
    $this->messageRepositoryStub = Mockery::mock(MessageRepository::class);
    $this->messageEntity = $this->validMessageEntity();
    $this->messageDTO = new NewMessageDTO(
        1,
        IdentityProvider::Discord,
        'eae',
        $this->messageEntity->providerId,
        $this->messageEntity->providerMessageId,
        $this->messageEntity->channelId,
        content: $this->messageEntity->content,
        sentAt: CarbonImmutable::parse('2023-01-24'),
    );
});

afterEach(function (): void {
    Mockery::close();
});

test('persist message success', function (): void {
    $this->messageRepositoryStub
        ->shouldReceive('create')
        ->with($this->messageDTO, 'canhassi', $this->messageEntity->obtainedExperience)
        ->once()
        ->andReturn($this->messageEntity);

    $test = new PersistMessage($this->messageRepositoryStub);

    $test->handle($this->messageDTO, $this->messageEntity->obtainedExperience, 'canhassi');
});
