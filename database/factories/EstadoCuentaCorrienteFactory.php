<?php

namespace Database\Factories;

use App\Models\EstadoCuentaCorriente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para crear estados de cuenta corriente en tests
 */
class EstadoCuentaCorrienteFactory extends Factory
{
    protected $model = EstadoCuentaCorriente::class;

    public function definition(): array
    {
        return [
            'nombreEstado' => $this->faker->randomElement(['Activa', 'Bloqueada', 'Vencida', 'Pendiente de Aprobación']),
            'descripcion' => $this->faker->sentence(),
        ];
    }

    /**
     * Estado Activa
     */
    public function activa(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombreEstado' => 'Activa',
            'descripcion' => 'Cuenta corriente activa y al día',
        ]);
    }

    /**
     * Estado Bloqueada
     */
    public function bloqueada(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombreEstado' => 'Bloqueada',
            'descripcion' => 'Cuenta corriente bloqueada por mora',
        ]);
    }

    /**
     * Estado Pendiente de Aprobación
     */
    public function pendienteAprobacion(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombreEstado' => 'Pendiente de Aprobación',
            'descripcion' => 'Cuenta corriente pendiente de aprobación',
        ]);
    }

    /**
     * Estado Vencida
     */
    public function vencida(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombreEstado' => 'Vencida',
            'descripcion' => 'Cuenta corriente con saldos vencidos',
        ]);
    }
}
