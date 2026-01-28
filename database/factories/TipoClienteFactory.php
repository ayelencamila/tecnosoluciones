<?php

namespace Database\Factories;

use App\Models\TipoCliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para crear tipos de cliente en tests
 */
class TipoClienteFactory extends Factory
{
    protected $model = TipoCliente::class;

    public function definition(): array
    {
        return [
            'nombreTipo' => $this->faker->randomElement(['Mayorista', 'Minorista']),
            'descripcion' => $this->faker->sentence(),
            'activo' => true,
        ];
    }

    /**
     * Tipo Mayorista
     */
    public function mayorista(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombreTipo' => 'Mayorista',
            'descripcion' => 'Cliente mayorista con cuenta corriente',
        ]);
    }

    /**
     * Tipo Minorista
     */
    public function minorista(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombreTipo' => 'Minorista',
            'descripcion' => 'Cliente minorista (venta contado)',
        ]);
    }
}
