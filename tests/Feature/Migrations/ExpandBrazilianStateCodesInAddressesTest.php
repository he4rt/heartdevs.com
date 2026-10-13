<?php

declare(strict_types=1);

use App\Models\Address;
use He4rt\Identity\User\Models\User;

function runExpandBrazilianStateCodesMigration(): void
{
    $migration = require database_path('migrations/2026_10_11_125557_expand_brazilian_state_codes_in_addresses.php');

    $migration->up();
}

function createAddressWithState(string $country, ?string $state): Address
{
    return Address::factory()->forUser(User::factory()->create())->create([
        'country' => $country,
        'state' => $state,
        'city' => 'Sorocaba',
    ]);
}

it('replaces a Brazilian state code with the state name', function (string $storedState, string $expectedState): void {
    $address = createAddressWithState('BRA', $storedState);

    runExpandBrazilianStateCodesMigration();

    expect($address->fresh())
        ->state->toBe($expectedState)
        ->city->toBe('Sorocaba');
})->with([
    'SP' => ['SP', 'São Paulo'],
    'DF' => ['DF', 'Distrito Federal'],
    'lowercase with spaces' => [' rj ', 'Rio de Janeiro'],
]);

it('keeps state names, empty states and addresses outside Brazil unchanged', function (): void {
    $named = createAddressWithState('BRA', 'Minas Gerais');
    $empty = createAddressWithState('BRA', state: null);
    $foreign = createAddressWithState('USA', 'PA');

    runExpandBrazilianStateCodesMigration();

    expect($named->fresh()->state)->toBe('Minas Gerais')
        ->and($empty->fresh()->state)->toBeNull()
        ->and($foreign->fresh()->state)->toBe('PA');
});
