<?php

namespace Database\Factories;

use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // empresa_id explicito y no heredado del DEFAULT de la columna:
            // Eloquent no relee los valores por defecto despues del INSERT, de
            // modo que el modelo en memoria quedaba con empresa_id = null y el
            // usuario no resolvia ningun tenant durante el test.
            'empresa_id' => 1,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'rol_id' => fn () => Rol::firstOrCreate(
                ['nombre' => 'Administrador', 'empresa_id' => 1],
                ['descripcion' => 'Administrador del sistema', 'activo' => true, 'es_admin_empresa' => true]
            )->rol_id,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
