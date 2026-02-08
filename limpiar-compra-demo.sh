#!/bin/bash

# ============================================================================
# Script para limpiar datos de demo del Módulo de Compras
# ============================================================================

echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║       🧹 LIMPIEZA DEMO COMPRAS - TecnoSoluciones                 ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

./vendor/bin/sail artisan tinker --execute="
use App\Models\OrdenCompra;
use App\Models\SolicitudCotizacion;
use App\Models\CotizacionProveedor;
use App\Models\RespuestaCotizacion;
use App\Models\Stock;
use App\Models\Configuracion;

echo '🧹 Limpiando datos de demo...' . PHP_EOL . PHP_EOL;

// 1. Eliminar respuestas de cotizaciones de hoy
\$respuestasEliminadas = RespuestaCotizacion::whereHas('cotizacionProveedor', function(\$q) {
    \$q->whereDate('created_at', today());
})->delete();
echo '   ✅ Respuestas eliminadas: ' . \$respuestasEliminadas . PHP_EOL;

// 2. Eliminar OCs con todas sus dependencias
\$ocs = OrdenCompra::whereDate('created_at', today())->get();
\$ocsEliminadas = 0;
foreach (\$ocs as \$oc) {
    foreach (\$oc->recepciones ?? [] as \$recepcion) {
        \$recepcion->detalles()->delete();
        \$recepcion->delete();
    }
    \$oc->detalles()->delete();
    \$oc->delete();
    \$ocsEliminadas++;
}
echo '   ✅ Órdenes de Compra eliminadas: ' . \$ocsEliminadas . PHP_EOL;

// 3. Eliminar cotizaciones de proveedores de hoy
\$cotizacionesEliminadas = CotizacionProveedor::whereDate('created_at', today())->delete();
echo '   ✅ Cotizaciones proveedores eliminadas: ' . \$cotizacionesEliminadas . PHP_EOL;

// 4. Eliminar solicitudes de hoy (con sus detalles)
\$solicitudes = SolicitudCotizacion::whereDate('created_at', today())->get();
\$solEliminadas = 0;
foreach (\$solicitudes as \$sol) {
    \$sol->detalles()->delete();
    \$sol->delete();
    \$solEliminadas++;
}
echo '   ✅ Solicitudes eliminadas: ' . \$solEliminadas . PHP_EOL;

// 5. Restaurar stocks a valores normales
\$stocksRestaurados = Stock::where('stock_minimo', 15)
    ->where('cantidad_disponible', '<', 5)
    ->update(['cantidad_disponible' => 20, 'stock_minimo' => 5]);
echo '   ✅ Stocks restaurados: ' . \$stocksRestaurados . PHP_EOL;

// 6. Desactivar automatización
Configuracion::set('compras_generacion_automatica', 'false');
echo '   ✅ Automatización desactivada' . PHP_EOL;

// 7. Limpiar cola de jobs
DB::table('jobs')->delete();
DB::table('failed_jobs')->truncate();
echo '   ✅ Cola de jobs limpiada' . PHP_EOL;

echo PHP_EOL . '✅ Limpieza completada. Podés ejecutar ./crear-compra-demo.sh de nuevo.' . PHP_EOL;
"

echo ""
echo "✅ Limpieza completada."
echo ""
