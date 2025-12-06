#!/bin/bash

# Script para crear cliente moroso para demo CU-09

./vendor/bin/sail artisan tinker --execute="
use App\Models\Cliente;
use App\Models\CuentaCorriente;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\MovimientoCuentaCorriente;
use Carbon\Carbon;

// Buscar o crear cliente Juan Pérez Moroso
\$cliente = Cliente::firstOrCreate(
    ['dni' => '99999999'],
    [
        'nombre' => 'Juan',
        'apellido' => 'Pérez Moroso',
        'telefono' => '1234567890',
        'email' => 'juan.moroso@test.com',
        'direccion' => 'Calle Falsa 123',
        'localidad' => 'Ciudad',
        'provincia' => 'Provincia',
        'tipoClienteID' => 2,
        'estadoClienteID' => 1,
    ]
);

// Crear o actualizar cuenta corriente
\$cc = \$cliente->cuentaCorriente;
if (!\$cc) {
    \$cc = CuentaCorriente::create([
        'clienteID' => \$cliente->clienteID,
        'limiteCredito' => 50000,
        'saldo' => 0,
        'estadoCuentaCorrienteID' => 1,
    ]);
}

// Obtener un producto con stock
\$producto = Producto::where('stock', '>', 10)->first();
\$total = \$producto->precioMayorista * 2;
\$fechaVenta = Carbon::now()->subDays(60);

// Crear venta de hace 60 días
\$venta = Venta::create([
    'clienteID' => \$cliente->clienteID,
    'formaPago' => 'Cuenta Corriente',
    'total' => \$total,
    'estadoVentaID' => 2,
    'created_at' => \$fechaVenta,
    'updated_at' => \$fechaVenta,
]);

// Crear detalle de venta
DetalleVenta::create([
    'ventaID' => \$venta->ventaID,
    'productoID' => \$producto->productoID,
    'cantidad' => 2,
    'precioUnitario' => \$producto->precioMayorista,
    'subtotal' => \$total,
]);

// Crear movimiento CC con vencimiento hace 35 días (VENCIDO)
\$fechaVencimiento = Carbon::now()->subDays(35);
MovimientoCuentaCorriente::create([
    'cuentaCorrienteID' => \$cc->cuentaCorrienteID,
    'ventaID' => \$venta->ventaID,
    'tipoMovimiento' => 'Debito',
    'monto' => \$total,
    'saldo' => \$cc->saldo + \$total,
    'fechaVencimiento' => \$fechaVencimiento,
    'descripcion' => 'Venta vencida - Demo CU-09',
    'created_at' => \$fechaVenta,
]);

// Actualizar saldo
\$cc->saldo += \$total;
\$cc->save();

// Mostrar resultado
\$saldoVencido = \$cc->calcularSaldoVencido();
echo '✅ Cliente moroso creado:' . PHP_EOL;
echo '   Nombre: ' . \$cliente->nombre . ' ' . \$cliente->apellido . PHP_EOL;
echo '   DNI: ' . \$cliente->dni . PHP_EOL;
echo '   Cliente ID: ' . \$cliente->clienteID . PHP_EOL;
echo '   Venta ID: ' . \$venta->ventaID . ' (\$' . number_format(\$total, 2) . ')' . PHP_EOL;
echo '   Saldo Vencido: \$' . number_format(\$saldoVencido, 2) . PHP_EOL;
echo '   Estado: ' . \$cliente->estadoCliente->nombre . PHP_EOL;
"

echo ""
echo "Ahora puedes demostrar el CU-09 ejecutando:"
echo "  ./vendor/bin/sail artisan cc:check-vencimientos"
