<?php

declare(strict_types=1);

use Tests\Unit\Meeting\MeetingProviderTrait;
use Heart\Meeting\Domain\Actions\CreateMeeting;
use Heart\Meeting\Domain\DTO\NewMeetingDTO;
use Heart\Meeting\Domain\Repositories\MeetingRepository;

uses(MeetingProviderTrait::class);

beforeEach(function (): void {
    $this->meetingTypeRepositoryStub = m::mock(MeetingRepository::class);
    $this->meetingEntity = $this->validMeetingEntity();
    $this->payloadDTO = NewMeetingDTO::make(
        'discord',
        'canhassi',
        $this->meetingEntity->meetingTypeId
    );
});
afterEach(function (): void {
    m::close();
});
test('create meeting', function (): void {
    $this->meetingTypeRepositoryStub
        ->shouldReceive('create')
        ->with($this->payloadDTO, $this->meetingEntity->adminId)
        ->once()
        ->andReturn($this->meetingEntity);

    $test = new CreateMeeting($this->meetingTypeRepositoryStub);

    $test->handle($this->payloadDTO, $this->meetingEntity->adminId);
});
