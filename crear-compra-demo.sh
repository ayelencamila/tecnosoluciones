#!/bin/bash

# ============================================================================
# Script para demo del Módulo de Compras (CU-20, CU-21, CU-22)
# ============================================================================
# Este script solo PREPARA el escenario.
# El proceso automático del backend hace el resto:
# - Detecta stock bajo
# - Genera solicitud de cotización
# - Envía WhatsApp + Email automáticamente
# ============================================================================

echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║       🛒 DEMO MÓDULO DE COMPRAS - TecnoSoluciones                ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

# Paso 1: Preparar producto con stock bajo
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 Preparando escenario: Producto con stock bajo..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

./vendor/bin/sail artisan tinker --execute="
use App\Models\Producto;
use App\Models\Proveedor;

// Buscar producto Notebook o el primero disponible
\$producto = Producto::where('nombre', 'like', '%Notebook%')->first();
if (!\$producto) {
    \$producto = Producto::first();
}

if (\$producto) {
    // Configurar stock bajo
    \$producto->update([
        'cantidadStock' => 1,
        'stockMinimo' => 5,
        'stockMaximo' => 20,
    ]);
    
    echo '✅ Producto preparado:' . PHP_EOL;
    echo '   📱 ' . \$producto->nombre . PHP_EOL;
    echo '   📊 Stock actual: 1' . PHP_EOL;
    echo '   ⚠️  Stock mínimo: 5' . PHP_EOL;
    echo '   🔴 ALERTA: Stock bajo!' . PHP_EOL;
}

// Mostrar proveedor
\$proveedor = Proveedor::where('razon_social', 'Ricardo Fort')->first();
if (\$proveedor) {
    echo PHP_EOL . '✅ Proveedor destino:' . PHP_EOL;
    echo '   🏢 ' . \$proveedor->razon_social . PHP_EOL;
    echo '   📧 ' . \$proveedor->email . PHP_EOL;
    echo '   📱 ' . (\$proveedor->whatsapp ?? \$proveedor->telefono) . PHP_EOL;
}
"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🤖 Ejecutando PROCESO AUTOMÁTICO del backend..."
echo "   (stock:monitorear detecta → genera solicitud → envía)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

./vendor/bin/sail artisan stock:monitorear --forzar-envio

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📬 Procesando cola de mensajes (WhatsApp)..."
echo "   (En producción esto corre en segundo plano)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

./vendor/bin/sail artisan queue:work --stop-when-empty

echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║              ✅ PROCESO AUTOMÁTICO COMPLETADO                    ║"
echo "╠══════════════════════════════════════════════════════════════════╣"
echo "║                                                                  ║"
echo "║  El proveedor recibió:                                           ║"
echo "║    📧 Email con magic link → ver en http://localhost:8025        ║"
echo "║    📱 WhatsApp con magic link → revisar celular                  ║"
echo "║                                                                  ║"
echo "║  📍 PRÓXIMOS PASOS (manuales):                                   ║"
echo "║                                                                  ║"
echo "║  1. Responder como proveedor (magic link)                        ║"
echo "║  2. Ir a: http://localhost/solicitudes-cotizacion                ║"
echo "║  3. Elegir cotización → Generar OC                               ║"
echo "║  4. Ejecutar: sail artisan queue:work --once                     ║"
echo "║                                                                  ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""
