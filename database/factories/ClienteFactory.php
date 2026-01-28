<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\CuentaCorriente;
use App\Models\EstadoCliente;
use App\Models\TipoCliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para crear clientes en tests
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'DNI' => $this->faker->unique()->numerify('########'),
            'mail' => $this->faker->unique()->safeEmail(),
            'whatsapp' => $this->faker->numerify('+549##########'),
            'telefono' => $this->faker->numerify('##########'),
            'tipoClienteID' => TipoCliente::factory(),
            'estadoClienteID' => EstadoCliente::factory(),
            'cuentaCorrienteID' => null,
        ];
    }

    /**
     * Cliente mayorista con cuenta corriente
     */
    public function mayorista(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipoClienteID' => TipoCliente::factory()->mayorista(),
        ]);
    }

    /**
     * Cliente con cuenta corriente asignada
     */
    public function conCuentaCorriente(CuentaCorriente $cc = null): static
    {
        return $this->state(fn (array $attributes) => [
            'cuentaCorrienteID' => $cc?->cuentaCorrienteID ?? CuentaCorriente::factory(),
        ]);
    }
}
