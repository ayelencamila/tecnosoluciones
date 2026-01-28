<?php

namespace Database\Factories;

use App\Models\EstadoVenta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para crear estados de venta en tests
 */
class EstadoVentaFactory extends Factory
{
    protected $model = EstadoVenta::class;

    public function definition(): array
    {
        return [
            'nombreEstado' => 'Pendiente',
        ];
    }

    /**
     * Estado Pendiente
     */
    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombreEstado' => 'Pendiente',
        ]);
    }

    /**
     * Estado Completada
     */
    public function completada(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombreEstado' => 'Completada',
        ]);
    }

    /**
     * Estado Anulada
     */
    public function anulada(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombreEstado' => 'Anulada',
        ]);
    }
}
