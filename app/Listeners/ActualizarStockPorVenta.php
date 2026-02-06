<?php

namespace App\Listeners;

use App\Events\VentaRegistrada;

/**
 * @deprecated OBSOLETO — No usar.
 *
 * El stock ahora se gestiona directamente dentro de RegistrarVentaService (Sección D)
 * usando la tabla `stock` y `movimientos_stock` con `tipo_movimiento_id` (FK).
 *
 * Este listener fue removido del EventServiceProvider porque:
 * 1. Operaba sobre la columna legacy `productos.stockActual` (ya no es fuente de verdad).
 * 2. Usaba el campo `tipoMovimiento` (enum eliminado por migración).
 * 3. Duplicaba la lógica que el servicio ya ejecuta de forma atómica (ACID).
 *
 * Se conserva el archivo únicamente como referencia histórica.
 */
class ActualizarStockPorVenta
{
    public function handle(VentaRegistrada $event): void
    {
        // Deliberadamente vacío — ver RegistrarVentaService
    }
}
