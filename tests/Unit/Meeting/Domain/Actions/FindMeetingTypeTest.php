<?php

declare(strict_types=1);

use Tests\Unit\Meeting\MeetingTypeProviderTrait;
use Heart\Meeting\Domain\Actions\FindMeetingType;
use Heart\Meeting\Domain\Exceptions\MeetingException;
use Heart\Meeting\Domain\Repositories\MeetingTypeRepository;

uses(MeetingTypeProviderTrait::class);

beforeEach(function (): void {
    $this->meetingTypeRepositoryStub = m::mock(MeetingTypeRepository::class);
    $this->meetingEntity = $this->validMeetingTypeEntity();
});
afterEach(function (): void {
    m::close();
});
test('meeting type is not found', function (): void {
    $this->expectException(MeetingException::class);

    $this->meetingTypeRepositoryStub
        ->shouldReceive('findById')
        ->with(12)
        ->once()
        ->andReturn(null);

    $test = new FindMeetingType($this->meetingTypeRepositoryStub);

    $test->handle(12);
});
test('find meeting type success', function (): void {
    $this->meetingTypeRepositoryStub
        ->shouldReceive('findById')
        ->with(2)
        ->once()
        ->andReturn($this->meetingEntity);

    $test = new FindMeetingType($this->meetingTypeRepositoryStub);

    $test->handle(2);
});
