<?php

namespace Database\Factories;

use App\Models\EstadoCliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para crear estados de cliente en tests
 */
class EstadoClienteFactory extends Factory
{
    protected $model = EstadoCliente::class;

    public function definition(): array
    {
        return [
            'nombreEstado' => 'Activo',
            'descripcion' => 'Cliente activo',
        ];
    }

    /**
     * Estado Activo
     */
    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombreEstado' => 'Activo',
        ]);
    }

    /**
     * Estado Inactivo
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombreEstado' => 'Inactivo',
        ]);
    }
}
