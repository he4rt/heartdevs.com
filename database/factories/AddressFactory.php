<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @extends Factory<Address>
 */
final class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'country' => 'BR',
            'state' => 'SP',
            'city' => fake()->city(),
            'zip_code' => fake()->postcode(),
        ];
    }

    public function forModel(Model $model): self
    {
        throw_if(!$model->exists || $model->getKey() === null, InvalidArgumentException::class, 'Model must be persisted before using forModel().');

        return $this->state([
            'addressable_type' => $model->getMorphClass(),
            'addressable_id' => $model->getKey(),
        ]);
    }

    public function forUser(User $user): self
    {
        return $this->forModel($user);
    }
}
