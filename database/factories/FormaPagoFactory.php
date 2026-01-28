<?php

namespace Database\Factories;

use App\Models\FormaPago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para crear formas de pago en tests
 */
class FormaPagoFactory extends Factory
{
    protected $model = FormaPago::class;

    public function definition(): array
    {
        return [
            'nombreFormaPago' => $this->faker->randomElement(['Efectivo', 'Tarjeta', 'Cuenta Corriente']),
            'descripcion' => $this->faker->sentence(),
            'activo' => true,
        ];
    }

    /**
     * Forma de pago Cuenta Corriente
     */
    public function cuentaCorriente(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombreFormaPago' => 'Cuenta Corriente',
            'descripcion' => 'Pago a cuenta corriente',
        ]);
    }

    /**
     * Forma de pago Efectivo
     */
    public function efectivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombreFormaPago' => 'Efectivo',
            'descripcion' => 'Pago en efectivo',
        ]);
    }
}
