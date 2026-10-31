<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanLimite;
use Illuminate\Database\Seeder;

/**
 * Planes comerciales iniciales.
 *
 * Los valores son de prueba y están pensados para ajustarse después desde la
 * base, sin tocar código ni desplegar.
 *
 * Nótese la diferencia de TIPO entre los límites del plan Básico:
 *
 *   usuarios            -> DURO   (capacidad: bloquear es inofensivo)
 *   ventas_mensuales    -> BLANDO (volumen: bloquear le frena la caja)
 *
 * Esa distinción es lo que evita que un tope comercial se convierta en una
 * caída de servicio para el cliente.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $basico = Plan::updateOrCreate(
            ['slug' => 'basico'],
            [
                'nombre' => 'Básico',
                'descripcion' => 'Para comercios chicos que recién arrancan.',
                'precio_mensual' => 0,
                'activo' => true,
                'orden' => 1,
            ]
        );

        $this->limites($basico, [
            ['clave' => 'usuarios', 'tipo' => PlanLimite::TIPO_DURO, 'valor' => 3],
            ['clave' => 'ventas_mensuales', 'tipo' => PlanLimite::TIPO_BLANDO, 'valor' => 100],
            ['clave' => 'reparaciones_mensuales', 'tipo' => PlanLimite::TIPO_BLANDO, 'valor' => 100],
        ]);

        $premium = Plan::updateOrCreate(
            ['slug' => 'premium'],
            [
                'nombre' => 'Premium',
                'descripcion' => 'Sin límites de uso.',
                'precio_mensual' => 0,
                'activo' => true,
                'orden' => 2,
            ]
        );

        // valor = null es SIN LÍMITE. Se declaran igual, en lugar de omitirlos,
        // para que la grilla de precios pueda mostrar "ilimitado" en vez de
        // dejar el renglón en blanco.
        $this->limites($premium, [
            ['clave' => 'usuarios', 'tipo' => PlanLimite::TIPO_DURO, 'valor' => null],
            ['clave' => 'ventas_mensuales', 'tipo' => PlanLimite::TIPO_BLANDO, 'valor' => null],
            ['clave' => 'reparaciones_mensuales', 'tipo' => PlanLimite::TIPO_BLANDO, 'valor' => null],
        ]);
    }

    /** @param list<array{clave:string,tipo:string,valor:int|null}> $limites */
    private function limites(Plan $plan, array $limites): void
    {
        foreach ($limites as $limite) {
            PlanLimite::updateOrCreate(
                ['plan_id' => $plan->id, 'clave' => $limite['clave']],
                ['tipo' => $limite['tipo'], 'valor' => $limite['valor']]
            );
        }
    }
}
