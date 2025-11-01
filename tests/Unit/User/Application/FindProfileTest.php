<?php

declare(strict_types=1);

use Tests\Unit\User\ProfileProviderTrait;
use Tests\Unit\Character\ProviderProviderTrait;
use Tests\Unit\User\UserProviderTrait;
use Heart\Provider\Domain\Repositories\ProviderRepository;
use Heart\User\Application\Exceptions\ProfileException;
use Heart\User\Application\FindProfile;
use Heart\User\Domain\Actions\GetProfile;
use Heart\User\Domain\Repositories\UserRepository;

uses(ProfileProviderTrait::class);

uses(ProviderProviderTrait::class);

uses(UserProviderTrait::class);

beforeEach(function (): void {
    $this->userRepositoryStub = m::mock(UserRepository::class);
    $this->getProfileStub = m::mock(GetProfile::class);
    $this->providerRepositoryStub = m::mock(ProviderRepository::class);
    $this->providerEntity = $this->validProviderEntity();
    $this->userEntity = $this->validUserEntity();
    $this->profileEntity = $this->validProfileEntity();
});
afterEach(function (): void {
    m::close();
});
test('find profile with username success', function (): void {
    $this->userRepositoryStub
        ->shouldReceive('findByUsername')
        ->with('canhassi')
        ->once()
        ->andReturn($this->userEntity);

    $this->getProfileStub
        ->shouldReceive('handle')
        ->with($this->userEntity->id)
        ->once()
        ->andReturn($this->profileEntity);

    $test = new FindProfile($this->getProfileStub, $this->userRepositoryStub, $this->providerRepositoryStub);

    $test->handle('canhassi');
});
test('find profile with provider id success', function (): void {
    $this->userRepositoryStub
        ->shouldReceive('findByUsername')
        ->with('canhassi-id')
        ->once();

    $this->providerRepositoryStub
        ->shouldReceive('findByProviderId')
        ->with('canhassi-id')
        ->once()
        ->andReturn($this->providerEntity);

    $this->getProfileStub
        ->shouldReceive('handle')
        ->with($this->providerEntity->userId)
        ->once()
        ->andReturn($this->profileEntity);

    $test = new FindProfile($this->getProfileStub, $this->userRepositoryStub, $this->providerRepositoryStub);

    $test->handle('canhassi-id');
});
test('profile not found', function (): void {
    $this->expectException(ProfileException::class);

    $this->userRepositoryStub
        ->shouldReceive('findByUsername')
        ->with('canhassi-id')
        ->once();

    $this->providerRepositoryStub
        ->shouldReceive('findByProviderId')
        ->with('canhassi-id')
        ->once();

    $test = new FindProfile($this->getProfileStub, $this->userRepositoryStub, $this->providerRepositoryStub);

    $test->handle('canhassi-id');
});
