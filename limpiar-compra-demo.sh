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
use App\Models\Producto;

echo '🧹 Limpiando datos de demo...' . PHP_EOL . PHP_EOL;

// 1. Eliminar respuestas de cotizaciones de hoy
\$respuestasEliminadas = RespuestaCotizacion::whereHas('cotizacionProveedor', function(\$q) {
    \$q->whereDate('created_at', today());
})->delete();
echo '   ✅ Respuestas eliminadas: ' . \$respuestasEliminadas . PHP_EOL;

// 2. Eliminar OCs con todas sus dependencias (en orden correcto)
\$ocs = OrdenCompra::whereDate('created_at', today())->get();
\$ocsEliminadas = 0;
foreach (\$ocs as \$oc) {
    // Primero eliminar recepciones y sus detalles
    foreach (\$oc->recepciones ?? [] as \$recepcion) {
        \$recepcion->detalles()->delete();
        \$recepcion->delete();
    }
    // Luego eliminar detalles de la OC
    \$oc->detalles()->delete();
    // Finalmente eliminar la OC
    \$oc->delete();
    \$ocsEliminadas++;
}
echo '   ✅ Órdenes de Compra eliminadas: ' . \$ocsEliminadas . PHP_EOL;

// 3. Desmarcar cotizaciones elegidas
CotizacionProveedor::where('elegida', 1)->update(['elegida' => 0]);
echo '   ✅ Cotizaciones desmarcadas' . PHP_EOL;

// 4. Restaurar stock del producto de prueba
\$producto = Producto::where('nombre', 'like', '%Notebook%')->first();
if (\$producto) {
    \$producto->update([
        'cantidadStock' => 10,
        'stockMinimo' => 5,
    ]);
    echo '   ✅ Stock restaurado: ' . \$producto->nombre . ' → 10 unidades' . PHP_EOL;
}

// 5. Limpiar cola de jobs
DB::table('jobs')->delete();
DB::table('failed_jobs')->delete();
echo '   ✅ Cola de jobs limpiada' . PHP_EOL;

echo PHP_EOL . '🎉 Limpieza completada!' . PHP_EOL;
"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Sistema listo para nueva demo"
echo "   Ejecutar: ./crear-compra-demo.sh"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
