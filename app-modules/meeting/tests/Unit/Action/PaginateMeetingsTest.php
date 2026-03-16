<?php

declare(strict_types=1);

use App\Contracts\Paginator;
use He4rt\Meeting\Actions\PaginateMeetingsAction;
use He4rt\Meeting\Contracts\MeetingRepository;
use He4rt\Meeting\Tests\Unit\MeetingProviderTrait;

uses(MeetingProviderTrait::class);

beforeEach(function (): void {
    $this->meetingRepositoryStub = Mockery::mock(MeetingRepository::class);
    $this->meetingEntity = $this->validMeetingEntity();
    $this->paginatorStub = Mockery::mock(Paginator::class);
});

afterEach(function (): void {
    Mockery::close();
});

test('paginate meetings', function (): void {
    $this->meetingRepositoryStub
        ->shouldReceive('paginate')
        ->with(['meetingType'])
        ->once()
        ->andReturn($this->paginatorStub);

    $test = new PaginateMeetingsAction($this->meetingRepositoryStub);

    $test->handle();
});
