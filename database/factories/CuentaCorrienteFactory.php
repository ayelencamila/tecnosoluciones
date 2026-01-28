<?php

namespace Database\Factories;

use App\Models\CuentaCorriente;
use App\Models\EstadoCuentaCorriente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para crear cuentas corrientes en tests
 */
class CuentaCorrienteFactory extends Factory
{
    protected $model = CuentaCorriente::class;

    public function definition(): array
    {
        return [
            'saldo' => 0,
            'limiteCredito' => 100000,
            'diasGracia' => 30,
            'estadoCuentaCorrienteID' => EstadoCuentaCorriente::factory()->activa(),
        ];
    }

    /**
     * Cuenta con saldo específico
     */
    public function conSaldo(float $saldo): static
    {
        return $this->state(fn (array $attributes) => [
            'saldo' => $saldo,
        ]);
    }

    /**
     * Cuenta con límite específico
     */
    public function conLimite(float $limite): static
    {
        return $this->state(fn (array $attributes) => [
            'limiteCredito' => $limite,
        ]);
    }

    /**
     * Cuenta bloqueada
     */
    public function bloqueada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estadoCuentaCorrienteID' => EstadoCuentaCorriente::factory()->bloqueada(),
        ]);
    }

    /**
     * Cuenta activa
     */
    public function activa(): static
    {
        return $this->state(fn (array $attributes) => [
            'estadoCuentaCorrienteID' => EstadoCuentaCorriente::factory()->activa(),
        ]);
    }
}
