<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use Carbon\Carbon;

class VentaSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener clientes y productos existentes
        $clientes = Cliente::take(5)->get();
        $productos = Producto::with('precios')->take(10)->get();
        $usuario = User::first();

        if ($clientes->isEmpty() || $productos->isEmpty() || !$usuario) {
            $this->command->warn('⚠️  No hay clientes, productos o usuarios. Ejecuta sus seeders primero.');
            return;
        }

        $ventasCreadas = 0;

        // Crear 15 ventas de ejemplo
        for ($i = 0; $i < 15; $i++) {
            $cliente = $clientes->random();
            $fechaVenta = Carbon::now()->subDays(rand(0, 60));
            
            // Calcular fecha de vencimiento solo si el cliente tiene CC activa
            $fechaVencimiento = null;
            if ($cliente->cuentaCorriente) {
                $diasGracia = $cliente->cuentaCorriente->getDiasGraciaAplicables();
                $fechaVencimiento = $fechaVenta->copy()->addDays($diasGracia);
            }

            // Seleccionar entre 1 y 4 productos al azar
            $productosVenta = $productos->random(rand(1, 4));
            $subtotal = 0;
            $detalles = [];

            foreach ($productosVenta as $producto) {
                $cantidad = rand(1, 5);
                $precio = $producto->precios->first()?->precio ?? 100;
                $subtotalItem = $cantidad * $precio;
                $subtotal += $subtotalItem;

                $detalles[] = [
                    'productoID' => $producto->id,
                    'precio_producto_id' => $producto->precios->first()?->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotalItem,
                    'descuento_item' => 0,
                    'subtotal_neto' => $subtotalItem,
                ];
            }

            // Crear la venta
            $venta = Venta::create([
                'clienteID' => $cliente->clienteID,
                'user_id' => $usuario->id,
                'numero_comprobante' => 'V-' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                'fecha_venta' => $fechaVenta,
                'fecha_vencimiento' => $fechaVencimiento,
                'subtotal' => $subtotal,
                'total_descuentos' => 0,
                'total' => $subtotal,
                'observaciones' => $i % 3 === 0 ? 'Venta de ejemplo generada por seeder' : null,
                'anulada' => $i === 14 ? true : false, // La última está anulada
                'motivo_anulacion' => $i === 14 ? 'Venta de prueba anulada por el seeder' : null,
            ]);

            // Crear los detalles
            foreach ($detalles as $detalle) {
                DetalleVenta::create([
                    'venta_id' => $venta->venta_id,
                    'productoID' => $detalle['productoID'],
                    'precio_producto_id' => $detalle['precio_producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'subtotal' => $detalle['subtotal'],
                    'descuento_item' => $detalle['descuento_item'],
                    'subtotal_neto' => $detalle['subtotal_neto'],
                ]);
            }

            $ventasCreadas++;
        }

        $this->command->info("✅ Se crearon {$ventasCreadas} ventas de ejemplo con sus detalles.");
    }
}
