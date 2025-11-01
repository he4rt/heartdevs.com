<?php

declare(strict_types=1);

use Tests\Unit\Message\MessageProviderTrait;
use Heart\Message\Domain\Actions\PersistMessage;
use Heart\Message\Domain\DTO\NewMessageDTO;
use Heart\Message\Domain\Repositories\MessageRepository;
use Heart\Provider\Domain\Enums\ProviderEnum;
use Illuminate\Support\Facades\Date;

uses(MessageProviderTrait::class);

beforeEach(function (): void {
    $this->messageRepositoryStub = m::mock(MessageRepository::class);
    $this->messageEntity = $this->validMessageEntity();
    $this->messageDTO = new NewMessageDTO(
        ProviderEnum::Discord,
        $this->messageEntity->providerId,
        $this->messageEntity->providerMessageId,
        $this->messageEntity->channelId,
        $this->messageEntity->content,
        Date::parse('2023-01-24') // sentAt in string
    );
});
afterEach(function (): void {
    m::close();
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
