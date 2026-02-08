#!/bin/bash

# ============================================================================
# Script de Demo: Proceso Automatizado de Compras (CU-20, CU-21, CU-22)
# ============================================================================
# Simula el flujo completo que en producción dispara el cron:
#   1. Detectar stock bajo → 2. Crear solicitud → 3. Enviar a TODOS los proveedores
#   4. Proveedor responde (magic link) → 5. Admin compara → 6. Elige ganador → 7. OC
# ============================================================================

echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║       🛒 DEMO PROCESO AUTOMATIZADO DE COMPRAS                    ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

# ──────────────────────────────────────────────────────────────────
# PASO 1: Preparar escenario (stock bajo + config activada)
# ──────────────────────────────────────────────────────────────────
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 PASO 1: Preparando escenario..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

./vendor/bin/sail artisan tinker --execute="
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Deposito;
use App\Models\Configuracion;

echo PHP_EOL;

// ── Activar automatización ──
Configuracion::set('compras_generacion_automatica', 'true');
Configuracion::set('compras_dias_vencimiento', '7');
Configuracion::set('compras_dias_recordatorio', '2');
Configuracion::set('compras_max_recordatorios', '3');
echo '⚙️  Automatización ACTIVADA' . PHP_EOL;
echo '   Vencimiento: 7 días | Recordatorio: cada 2 días | Máx: 3' . PHP_EOL . PHP_EOL;

// ── Preparar productos con stock bajo (solo productos, NO servicios) ──
\$productos = Producto::where('es_servicio', false)->take(3)->get();
\$deposito = Deposito::first();

if (\$productos->isEmpty() || !\$deposito) {
    echo '❌ No hay productos o depósitos. Ejecutá primero los seeders.' . PHP_EOL;
    exit(1);
}

echo '📦 Productos con stock bajo:' . PHP_EOL;
foreach (\$productos as \$i => \$p) {
    \$stock = Stock::firstOrCreate(
        ['productoID' => \$p->id, 'deposito_id' => \$deposito->deposito_id],
        ['cantidad_disponible' => 0, 'stock_minimo' => 0]
    );
    \$stock->update([
        'cantidad_disponible' => rand(1, 3),
        'stock_minimo' => 15,
    ]);
    echo '   🔴 ' . \$p->nombre . ' → Stock: ' . \$stock->cantidad_disponible . ' (Mín: 15)' . PHP_EOL;
}

// ── Mostrar proveedores activos ──
\$proveedores = Proveedor::where('activo', true)->get();
echo PHP_EOL . '🏢 Proveedores activos (' . \$proveedores->count() . '):' . PHP_EOL;
foreach (\$proveedores as \$prov) {
    \$canales = [];
    if (\$prov->email) \$canales[] = '📧 ' . \$prov->email;
    if (\$prov->telefono) \$canales[] = '📱 ' . \$prov->telefono;
    echo '   • ' . \$prov->razon_social . ' → ' . implode(' | ', \$canales) . PHP_EOL;
}
echo PHP_EOL . '📨 Se enviará a TODOS los proveedores activos.' . PHP_EOL;
"

echo ""
# ──────────────────────────────────────────────────────────────────
# PASO 2: Ejecutar monitoreo automático (simula el cron diario 8AM)
# ──────────────────────────────────────────────────────────────────
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🤖 PASO 2: Ejecutando stock:monitorear --forzar-envio"
echo "   (En producción esto corre diariamente a las 8:00 AM)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

./vendor/bin/sail artisan stock:monitorear --forzar-envio

echo ""
# ──────────────────────────────────────────────────────────────────
# PASO 3: Procesar cola (envía los magic links)
# ──────────────────────────────────────────────────────────────────
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📬 PASO 3: Procesando cola de envíos..."
echo "   (En producción el queue worker corre permanentemente)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

./vendor/bin/sail artisan queue:work --stop-when-empty 2>&1 || echo "   (Cola vacía o procesada)"

echo ""
# ──────────────────────────────────────────────────────────────────
# PASO 4: Mostrar magic links generados
# ──────────────────────────────────────────────────────────────────
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔗 PASO 4: Magic Links generados"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

./vendor/bin/sail artisan tinker --execute="
use App\Models\CotizacionProveedor;

\$cotizaciones = CotizacionProveedor::with('proveedor')
    ->whereDate('created_at', today())
    ->latest()
    ->get();

if (\$cotizaciones->isEmpty()) {
    echo '⚠️  No se generaron cotizaciones hoy.' . PHP_EOL;
} else {
    echo PHP_EOL;
    foreach (\$cotizaciones as \$cot) {
        \$estado = \$cot->fecha_respuesta ? '✅ Respondida' : '⏳ Pendiente';
        echo '🏢 ' . \$cot->proveedor->razon_social . ' [' . \$estado . ']' . PHP_EOL;
        echo '   🔗 ' . \$cot->generarMagicLink() . PHP_EOL . PHP_EOL;
    }
}
"

echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║              ✅ PROCESO AUTOMÁTICO COMPLETADO                    ║"
echo "╠══════════════════════════════════════════════════════════════════╣"
echo "║                                                                  ║"
echo "║  Los proveedores recibieron:                                     ║"
echo "║    📧 Email con magic link → ver en http://localhost:8025        ║"
echo "║    📱 WhatsApp con magic link → revisar Twilio/celular          ║"
echo "║                                                                  ║"
echo "║  📍 PRÓXIMOS PASOS:                                              ║"
echo "║                                                                  ║"
echo "║  1. Abrí los magic links de arriba (uno por proveedor)          ║"
echo "║  2. Respondé como proveedor (activá/desactivá productos)        ║"
echo "║  3. Entrá al sistema → Compras → Solicitudes de Cotización      ║"
echo "║  4. Abrí la solicitud → Comparar → Elegir Ganador               ║"
echo "║  5. (Opcional) Probá recordatorios:                              ║"
echo "║     sail artisan cotizaciones:enviar-recordatorios               ║"
echo "║  6. (Opcional) Probá cierre automático:                          ║"
echo "║     sail artisan cotizaciones:cerrar-vencidas                    ║"
echo "║                                                                  ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""
