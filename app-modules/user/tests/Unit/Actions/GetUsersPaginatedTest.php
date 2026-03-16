<?php

declare(strict_types=1);

use App\Contracts\Paginator;
use He4rt\User\Actions\GetUsersPaginated;
use He4rt\User\Contracts\UserRepository;
use Mockery as m;

beforeEach(function (): void {
    $this->repositoryStub = m::mock(UserRepository::class);
    $this->paginatorStub = m::mock(Paginator::class);
});
afterEach(function (): void {
    m::close();
});
test('get users paginated', function (): void {
    $this->repositoryStub
        ->shouldReceive('paginated')
        ->once()
        ->andReturn($this->paginatorStub);

    $test = new GetUsersPaginated($this->repositoryStub);

    $test->handle();
});
