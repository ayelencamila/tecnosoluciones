<?php

use App\Models\Reparacion;
use App\Models\User;
use App\Models\Venta;

return [

    /*
    |--------------------------------------------------------------------------
    | Catálogo de límites
    |--------------------------------------------------------------------------
    |
    | Acá se define QUÉ se puede limitar y CÓMO se cuenta cada cosa. Los
    | VALORES de cada límite no están acá: viven en la tabla `plan_limites`,
    | para que ajustar un número sea una decisión comercial que no requiera un
    | deploy.
    |
    | `tipo` es el valor por defecto; cada plan puede sobrescribirlo:
    |
    |   duro   -> al alcanzarlo se bloquea la creación del recurso.
    |             Solo para límites de CAPACIDAD, donde bloquear es inofensivo.
    |
    |   blando -> se cuenta y se avisa, nunca se bloquea.
    |             Para límites de VOLUMEN OPERATIVO, donde bloquear significa
    |             frenarle la caja al cliente.
    |
    | `contar` recibe el id de empresa y devuelve el consumo actual. Se
    | ejecuta SIN el scope de empresa activo (ver ControlDeLimites), así que
    | filtra por empresa_id de forma explícita.
    |
    */

    'limites' => [

        'usuarios' => [
            'label' => 'Usuarios del sistema',
            'descripcion' => 'Cuentas activas con acceso al sistema.',
            'tipo' => 'duro',
            'unidad' => 'usuarios',
            // Capacidad: se cuenta el total vigente, no un acumulado.
            'periodo' => null,
            'contar' => fn (int $empresaId): int => User::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)
                ->where('activo', true)
                ->count(),
        ],

        'ventas_mensuales' => [
            'label' => 'Ventas por mes',
            'descripcion' => 'Ventas registradas en el mes calendario en curso.',
            'tipo' => 'blando',
            'unidad' => 'ventas',
            'periodo' => 'mes calendario',
            /*
             * Mes CALENDARIO, no 30 días corridos ni ciclo de facturación.
             * Es la definición más simple de explicarle a un cliente ("del 1
             * al 31") y la única que da el mismo número si se consulta dos
             * veces el mismo día.
             *
             * Las ventas ANULADAS no consumen cupo: una corrección legítima
             * no debería costarle al cliente parte de su plan.
             */
            'contar' => fn (int $empresaId): int => Venta::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)
                // Las anuladas se excluyen por estado. Se compara contra el
                // NOMBRE y no contra un id fijo porque `estados_venta` es un
                // catálogo global que podría reordenarse.
                ->whereHas('estado', fn ($q) => $q->where('nombreEstado', '!=', 'Anulada'))
                ->whereBetween('fecha_venta', [
                    now()->startOfMonth(),
                    now()->endOfMonth(),
                ])
                ->count(),
        ],

        'reparaciones_mensuales' => [
            'label' => 'Reparaciones por mes',
            'descripcion' => 'Equipos ingresados en el mes calendario en curso.',
            'tipo' => 'blando',
            'unidad' => 'reparaciones',
            'periodo' => 'mes calendario',
            'contar' => fn (int $empresaId): int => Reparacion::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)
                ->where('anulada', false)
                ->whereBetween('fecha_ingreso', [
                    now()->startOfMonth(),
                    now()->endOfMonth(),
                ])
                ->count(),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Umbral de aviso
    |--------------------------------------------------------------------------
    |
    | Porcentaje del límite a partir del cual se avisa que se está cerca.
    | Avisar recién al 100% no sirve: para entonces ya es tarde para decidir.
    |
    */

    'umbral_aviso' => 0.8,

];
