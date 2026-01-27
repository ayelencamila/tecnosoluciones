#!/bin/bash

# Script para crear cliente moroso para demo CU-09
# Actualizado para coincidir con el esquema de BD actual

./vendor/bin/sail artisan tinker --execute="
use App\Models\Cliente;
use App\Models\CuentaCorriente;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\MovimientoCuentaCorriente;
use App\Models\TipoCliente;
use App\Models\EstadoCliente;
use App\Models\EstadoVenta;
use App\Models\EstadoCuentaCorriente;
use App\Models\MedioPago;
use Carbon\Carbon;

echo '🔄 Creando cliente moroso para demo CU-09...' . PHP_EOL;

// Obtener IDs necesarios
\$tipoMayorista = TipoCliente::where('nombreTipo', 'Mayorista')->first();
\$estadoActivo = EstadoCliente::where('nombreEstado', 'Activo')->first();
\$estadoVentaCompletada = EstadoVenta::where('nombreEstado', 'Completada')->first();
\$estadoCCActiva = EstadoCuentaCorriente::activa();
\$medioPagoCC = MedioPago::where('nombre', 'like', '%Cuenta%')->orWhere('nombre', 'like', '%Credito%')->first();

if (!\$tipoMayorista || !\$estadoActivo) {
    echo '❌ Error: No se encontraron los tipos/estados base.' . PHP_EOL;
    exit(1);
}

// Buscar o crear cliente Juan Pérez Moroso
\$cliente = Cliente::firstOrCreate(
    ['DNI' => '99999999'],
    [
        'nombre' => 'Juan',
        'apellido' => 'Pérez Moroso',
        'whatsapp' => '+5491123456789',
        'telefono' => '1123456789',
        'mail' => 'juan.moroso@test.com',
        'tipoClienteID' => \$tipoMayorista->tipoClienteID,
        'estadoClienteID' => \$estadoActivo->estadoClienteID,
    ]
);

echo '   Cliente: ' . \$cliente->nombre . ' ' . \$cliente->apellido . ' (ID: ' . \$cliente->clienteID . ')' . PHP_EOL;

// Crear o actualizar cuenta corriente
\$cc = CuentaCorriente::where('cuentaCorrienteID', \$cliente->cuentaCorrienteID)->first();
if (!\$cc) {
    \$cc = CuentaCorriente::create([
        'saldo' => 0,
        'limiteCredito' => 50000,
        'diasGracia' => 30, // Días de gracia específicos
        'estadoCuentaCorrienteID' => \$estadoCCActiva->estadoCuentaCorrienteID,
    ]);
    \$cliente->cuentaCorrienteID = \$cc->cuentaCorrienteID;
    \$cliente->save();
    echo '   CC creada: ID ' . \$cc->cuentaCorrienteID . PHP_EOL;
} else {
    // Asegurarse de que esté activa para la demo
    \$cc->estadoCuentaCorrienteID = \$estadoCCActiva->estadoCuentaCorrienteID;
    \$cc->save();
    echo '   CC existente: ID ' . \$cc->cuentaCorrienteID . PHP_EOL;
}

// Obtener un producto
\$producto = Producto::whereHas('estado', fn(\$q) => \$q->where('nombre', 'Activo'))->first();
if (!\$producto) {
    echo '❌ Error: No hay productos activos.' . PHP_EOL;
    exit(1);
}

\$precioUnitario = 15000; // Precio fijo para demo
\$cantidad = 5;
\$total = \$precioUnitario * \$cantidad; // \$75,000
\$fechaVenta = Carbon::now()->subDays(65); // Hace 65 días

// Crear venta antigua
\$venta = Venta::create([
    'clienteID' => \$cliente->clienteID,
    'user_id' => 1,
    'estado_venta_id' => \$estadoVentaCompletada?->estadoVentaID ?? 2,
    'medio_pago_id' => \$medioPagoCC?->medioPagoID ?? 2,
    'numero_comprobante' => 'DEMO-CU09-' . time(),
    'fecha_venta' => \$fechaVenta,
    'fecha_vencimiento' => \$fechaVenta->copy()->addDays(30), // Venció hace 35 días
    'subtotal' => \$total,
    'total_descuentos' => 0,
    'total' => \$total,
    'created_at' => \$fechaVenta,
    'updated_at' => \$fechaVenta,
]);

echo '   Venta creada: ID ' . \$venta->venta_id . ' - \$' . number_format(\$total, 2) . PHP_EOL;

// Crear detalle de venta
DetalleVenta::create([
    'venta_id' => \$venta->venta_id,
    'producto_id' => \$producto->id,
    'cantidad' => \$cantidad,
    'precio_unitario' => \$precioUnitario,
    'subtotal' => \$total,
    'descuento_item' => 0,
    'subtotal_neto' => \$total,
]);

// Obtener tipo movimiento Débito
\$tipoDebito = DB::table('tipos_movimiento_cuenta_corriente')
    ->where('nombre', 'Debito')
    ->first();

// Crear movimiento CC con vencimiento VENCIDO (hace 35 días superó los días de gracia)
\$fechaVencimiento = Carbon::now()->subDays(35);
MovimientoCuentaCorriente::create([
    'cuentaCorrienteID' => \$cc->cuentaCorrienteID,
    'tipo_movimiento_cc_id' => \$tipoDebito->tipo_id,
    'descripcion' => 'Venta #' . \$venta->venta_id . ' - Demo CU-09',
    'monto' => \$total,
    'fechaEmision' => \$fechaVenta,
    'fechaVencimiento' => \$fechaVencimiento,
    'saldoAlMomento' => \$cc->saldo + \$total,
    'referenciaID' => \$venta->venta_id,
    'referenciaTabla' => 'ventas',
    'created_at' => \$fechaVenta,
    'updated_at' => \$fechaVenta,
]);

// Actualizar saldo de la CC
\$cc->saldo = \$cc->saldo + \$total;
\$cc->save();

// Mostrar resultado
\$cc->refresh();
\$saldoVencido = \$cc->calcularSaldoVencido();
\$limiteAplicable = \$cc->getLimiteCreditoAplicable();
\$diasGracia = \$cc->getDiasGraciaAplicables();

echo PHP_EOL;
echo '═══════════════════════════════════════════════════════════════' . PHP_EOL;
echo '✅ CLIENTE MOROSO CREADO EXITOSAMENTE' . PHP_EOL;
echo '═══════════════════════════════════════════════════════════════' . PHP_EOL;
echo '   👤 Nombre: ' . \$cliente->nombre . ' ' . \$cliente->apellido . PHP_EOL;
echo '   🆔 DNI: ' . \$cliente->DNI . PHP_EOL;
echo '   📱 WhatsApp: ' . \$cliente->whatsapp . PHP_EOL;
echo '   💳 CC ID: ' . \$cc->cuentaCorrienteID . PHP_EOL;
echo '   💰 Saldo Total: \$' . number_format(\$cc->saldo, 2) . PHP_EOL;
echo '   ⏰ Saldo Vencido: \$' . number_format(\$saldoVencido, 2) . PHP_EOL;
echo '   📊 Límite Crédito: \$' . number_format(\$limiteAplicable, 2) . PHP_EOL;
echo '   📅 Días de Gracia: ' . \$diasGracia . PHP_EOL;
echo '   🔘 Estado CC: ' . \$cc->estadoCuentaCorriente->nombreEstado . PHP_EOL;
echo '   🔘 Estado Cliente: ' . \$cliente->estadoCliente->nombreEstado . PHP_EOL;
echo '═══════════════════════════════════════════════════════════════' . PHP_EOL;
"

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "📋 SIGUIENTE PASO: Ejecutar el proceso CU-09:"
echo "   ./vendor/bin/sail artisan cc:check-vencimientos"
echo ""
echo "   Esto debería:"
echo "   1. Detectar el incumplimiento (saldo vencido)"
echo "   2. Notificar al administrador (Panel + WhatsApp)"
echo "   3. Bloquear automáticamente la cuenta"
echo "   4. Cambiar estado del cliente a 'Moroso'"
echo "═══════════════════════════════════════════════════════════════"
