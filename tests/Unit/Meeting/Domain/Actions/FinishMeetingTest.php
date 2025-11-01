<?php

declare(strict_types=1);

use Tests\Unit\Meeting\MeetingProviderTrait;
use Heart\Meeting\Domain\Actions\FinishMeeting;
use Heart\Meeting\Domain\Repositories\MeetingRepository;

uses(MeetingProviderTrait::class);

beforeEach(function (): void {
    $this->meetingRepositoryStub = m::mock(MeetingRepository::class);
    $this->meetingEntity = $this->validMeetingEntity();
});
afterEach(function (): void {
    m::close();
});
test('finish meeting', function (): void {
    $this->meetingRepositoryStub
        ->shouldReceive('endMeeting')
        ->with($this->meetingEntity->id)
        ->once()
        ->andReturn($this->meetingEntity);

    $test = new FinishMeeting($this->meetingRepositoryStub);

    $test->handle($this->meetingEntity->id);
});
