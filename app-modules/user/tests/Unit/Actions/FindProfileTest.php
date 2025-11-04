<?php

declare(strict_types=1);

use He4rt\Character\Tests\Unit\ProviderProviderTrait;
use He4rt\Provider\Contracts\ProviderRepository;
use He4rt\User\Actions\FindProfile;
use He4rt\User\Actions\GetProfile;
use He4rt\User\Contracts\UserRepository;
use He4rt\User\Exceptions\ProfileException;
use He4rt\User\Tests\Unit\ProfileProviderTrait;
use He4rt\User\Tests\Unit\UserProviderTrait;

uses(ProfileProviderTrait::class, ProviderProviderTrait::class, UserProviderTrait::class);

beforeEach(function (): void {
    $this->userRepositoryStub = Mockery::mock(UserRepository::class);
    $this->providerRepositoryStub = Mockery::mock(ProviderRepository::class);
    $this->getProfile = new GetProfile($this->userRepositoryStub);

    $this->providerEntity = $this->validProviderEntity();
    $this->userEntity = $this->validUserEntity();
    $this->profileEntity = $this->validProfileEntity();
});

afterEach(function (): void {
    Mockery::close();
});

test('find profile with username success', function (): void {
    $this->userRepositoryStub
        ->shouldReceive('findByUsername')
        ->with('canhassi')
        ->once()
        ->andReturn($this->userEntity);

    $this->userRepositoryStub
        ->shouldReceive('findProfile')
        ->with($this->userEntity->id)
        ->once()
        ->andReturn($this->profileEntity);

    $test = new FindProfile($this->getProfile, $this->userRepositoryStub, $this->providerRepositoryStub);

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

    $this->userRepositoryStub
        ->shouldReceive('findProfile')
        ->with($this->providerEntity->modelId)
        ->once()
        ->andReturn($this->profileEntity);

    $test = new FindProfile($this->getProfile, $this->userRepositoryStub, $this->providerRepositoryStub);

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

    $this->userRepositoryStub
        ->shouldReceive('findProfile')
        ->never();

    $test = new FindProfile($this->getProfile, $this->userRepositoryStub, $this->providerRepositoryStub);

    $test->handle('canhassi-id');
});
