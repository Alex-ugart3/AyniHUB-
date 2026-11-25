<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'     => fake()->name(),
            'contact'  => fake()->unique()->safeEmail(),
            'email'    => null,
            'password' => Hash::make('Clave1234'),
            'role'     => 'comprador',
            'location' => null,
        ];
    }

    public function productor(): static
    {
        return $this->state(['role' => 'productor', 'location' => 'Chinchero']);
    }

    public function gestor(): static
    {
        return $this->state(['role' => 'gestor', 'location' => 'Chinchero']);
    }
}
