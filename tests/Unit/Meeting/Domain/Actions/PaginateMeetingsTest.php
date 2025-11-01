<?php

declare(strict_types=1);

use Tests\Unit\Meeting\MeetingProviderTrait;
use Heart\Meeting\Domain\Actions\PaginateMeetings;
use Heart\Meeting\Domain\Repositories\MeetingRepository;
use Heart\Shared\Domain\Paginator;

uses(MeetingProviderTrait::class);

beforeEach(function (): void {
    $this->meetingRepositoryStub = m::mock(MeetingRepository::class);
    $this->meetingEntity = $this->validMeetingEntity();
    $this->paginatorStub = m::mock(Paginator::class);
});
afterEach(function (): void {
    m::close();
});
test('paginate meetings', function (): void {
    $this->meetingRepositoryStub
        ->shouldReceive('paginate')
        ->with(['meetingType'])
        ->once()
        ->andReturn($this->paginatorStub);

    $test = new PaginateMeetings($this->meetingRepositoryStub);

    $test->handle();
});
