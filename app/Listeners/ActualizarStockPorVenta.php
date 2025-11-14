<?php

namespace App\Listeners;

use App\Events\VentaRegistrada;
use App\Events\StockUpdateFailed; 
use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Listener que descuenta el stock desde la columna 'stockActual' del producto.
 * (Arquitectura de Depósito Único)
 */
class ActualizarStockPorVenta implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(VentaRegistrada $event): void
    {
        $venta = $event->venta;
        $venta->loadMissing('detalles.producto'); 

        try {
            DB::transaction(function () use ($venta, $event) {
                
                foreach ($venta->detalles as $detalle) {
                    
                    $producto = $detalle->producto;
                    $cantidadVendida = $detalle->cantidad;

                    if (!$producto) {
                        Log::warning("Producto ID {$detalle->productoID} no encontrado al descontar stock para Venta ID {$venta->venta_id}");
                        continue;
                    }

                    $stockAnterior = $producto->stockActual;

                    if ($stockAnterior < $cantidadVendida) {
                        throw new \Exception("Stock insuficiente en BD (concurrencia) para Producto ID {$producto->id} en Venta ID {$venta->venta_id}");
                    }
                    
                    // 1. Descontar de 'productos.stockActual'
                    $producto->decrement('stockActual', $cantidadVendida);
                    
                    $stockNuevo = $stockAnterior - $cantidadVendida; // Para auditoría

                    // 2. Crear el Movimiento de Auditoría
                    // (Compatible con tu modelo MovimientoStock.php)
                    MovimientoStock::create([
                        'productoID' => $producto->id,
                        'tipoMovimiento' => 'Salida', // Tu migración usa ENUM, asegúrate que 'Salida' sea válido
                        'cantidad' => $cantidadVendida,
                        'stockAnterior' => $stockAnterior,
                        'stockNuevo' => $stockNuevo,
                        'motivo' => 'Venta (Comprobante ' . $venta->numero_comprobante . ')',
                        'referenciaID' => $detalle->detalle_venta_id, // Asumo que esta es tu PK de detalle
                        'referenciaTabla' => 'detalle_ventas',
                        // No pasamos user_id ni fecha_movimiento porque no están en tu tabla/modelo
                    ]);
                }
            });

            Log::info("Stock (Depósito Único) actualizado para Venta ID: {$venta->venta_id}");

        } catch (\Exception $e) {
            Log::error("Error CRÍTICO al actualizar stock (Depósito Único) para Venta ID {$venta->venta_id}: " . $e->getMessage());
            event(new StockUpdateFailed($venta, $e));
        }
    }
}
