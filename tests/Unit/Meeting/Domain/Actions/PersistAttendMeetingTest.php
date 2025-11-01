<?php

declare(strict_types=1);

use Tests\Unit\Meeting\MeetingProviderTrait;
use Heart\Meeting\Domain\Actions\PersistAttendMeeting;
use Heart\Meeting\Domain\Repositories\MeetingRepository;

uses(MeetingProviderTrait::class);

beforeEach(function (): void {
    $this->meetingTypeRepositoryStub = m::mock(MeetingRepository::class);
    $this->meetingEntity = $this->validMeetingEntity();
});
afterEach(function (): void {
    m::close();
});
test('persist attend meeting', function (): void {
    $this->meetingTypeRepositoryStub
        ->shouldReceive('attendMeeting')
        ->with($this->meetingEntity->id, 12)
        ->once();

    $test = new PersistAttendMeeting($this->meetingTypeRepositoryStub);

    $test->handle($this->meetingEntity->id, 12);
});
